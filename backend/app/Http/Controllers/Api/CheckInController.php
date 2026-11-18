<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\LectureSession;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CheckInController extends Controller
{
    /**
     * Check in by scanning the QR code.
     *
     * Send the text inside the QR code and the phone's location. Returns 201 with the
     * attendance record, or 200 if the student already checked in. On a 422, show
     * `message` to the student (expired code, not in the room, check-in closed, ...).
     */
    public function store(Request $request, QrCodeService $qrCodes)
    {
        $request->validate([
            'code' => 'required|string|max:200',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy_meters' => 'required|numeric|min:0', // GPS accuracy reported by the phone
            'is_mocked' => 'sometimes|boolean', // true if the phone says the location is fake
            'device_uid' => 'required|string|max:100', // the phone's ID, sent by the app
        ]);

        $student = $request->user();
        $code = $request->code;
        $latitude = (float) $request->latitude;
        $longitude = (float) $request->longitude;
        $accuracy = (float) $request->accuracy_meters;

        // 0. The check-in must come from one of the student's own phones.
        $device = $student->devices()->where('device_uid', $request->device_uid)->first();
        if (! $device) {
            $this->reject('code', "This phone isn't registered to your account. Log out and log in again.");
        }

        // 1. Find the lecture from the code.
        $sessionId = $qrCodes->sessionIdFrom($code);
        $session = $sessionId ? LectureSession::find($sessionId) : null;
        if (! $session) {
            $this->reject('code', "This isn't a UniCheck attendance code.");
        }

        // 2. The student must be in this class, and check-in must be open.
        if (! $session->section->hasStudent($student)) {
            $this->reject('code', "You aren't enrolled in this class.");
        }
        if (! $session->check_in_open) {
            $this->reject('code', 'Check-in for this lecture is closed.');
        }

        // 3. Scanning twice is fine. But don't overwrite what the instructor set by hand.
        $existing = $session->attendanceRecords()->where('student_id', $student->id)->first();
        if ($existing && $existing->status === 'present') {
            return response()->json($existing, 200);
        }
        if ($existing) {
            $this->reject('code', 'Your instructor has already recorded your attendance for this lecture.');
        }

        // 4. The code must be from the last 10–20 seconds (stops screenshots sent to friends).
        $codeAge = $qrCodes->age($session, $code);
        if ($codeAge === null) {
            $this->reject('code', 'This QR code has expired. Scan the code on the screen again.');
        }

        // 5. The student must really be near the room.
        if ($request->boolean('is_mocked')) {
            $this->reject('location', 'Turn off fake-location apps to check in.');
        }

        $point = $session->checkInPoint(); // the section's location, or the room's
        if (! $point) {
            $this->reject('location', "Your instructor hasn't set where this class is yet.");
        }
        $distance = $this->distanceInMeters($latitude, $longitude, $point['latitude'], $point['longitude']);
        // GPS can be off by a bit, so we forgive the phone's reported error, but at most 50 m.
        $allowedError = min($accuracy, 50);
        if ($distance - $allowedError > $point['radius']) {
            $this->reject('location', 'You need to be in the lecture room to check in.');
        }

        // 6. Accept, but flag things the instructor may want to double-check.
        $flags = [];
        if ($codeAge === 1) {
            $flags[] = 'token_near_expiry'; // used the previous code
        }
        if ($accuracy > 50) {
            $flags[] = 'weak_location_signal';
        }
        if ($this->isFarFromClass($session, $latitude, $longitude)) {
            $flags[] = 'far_from_class';
        }
        if ($device->created_at->gt(now()->subDay()) && $student->devices()->count() > 1) {
            $flags[] = 'new_phone'; // added another phone in the last 24 hours
        }

        $record = AttendanceRecord::create([
            'lecture_session_id' => $session->id,
            'student_id' => $student->id,
            'status' => 'present',
            'source' => 'scan',
            'recorded_at' => now(),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy_meters' => $accuracy,
            'device_id' => $device->id,
            'flags' => $flags,
        ]);

        $device->update(['last_used_at' => now()]);

        return response()->json($record, 201);
    }

    // True if the student is more than 50 m from the middle of the students who already
    // checked in (only once at least 5 have).
    private function isFarFromClass(LectureSession $session, float $latitude, float $longitude): bool
    {
        $others = $session->attendanceRecords()->where('source', 'scan')->whereNotNull('latitude')->get();

        if ($others->count() < 5) {
            return false;
        }

        $distance = $this->distanceInMeters($latitude, $longitude, $others->median('latitude'), $others->median('longitude'));

        return $distance > 50;
    }

    // Distance between two GPS points in meters (haversine formula).
    private function distanceInMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $earthRadius * asin(sqrt($a));
    }

    // Stops the request with a 422 and a message for the student.
    private function reject(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
