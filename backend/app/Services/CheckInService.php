<?php

namespace App\Services;

use App\Enums\AttendanceFlag;
use App\Enums\AttendanceSource;
use App\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\LectureSession;
use App\Models\User;
use App\Support\Geo;
use Illuminate\Validation\ValidationException;

/**
 * Checks a student's QR scan and records them present.
 *
 * Every rule that can reject a check-in lives here, in this order: valid UniCheck code →
 * enrolled → check-in window open → code not expired or forged → real location → near the room.
 */
final class CheckInService
{
    /** GPS accuracy worse than this (in meters) is flagged for the instructor. */
    public const WEAK_ACCURACY_METERS = 50;

    /** A student further than this from the middle of the class is flagged. */
    public const FAR_FROM_CLASS_METERS = 50;

    /** Check-ins needed before "far from the class" can be judged. */
    public const MIN_CHECK_INS_FOR_CLASS_POSITION = 5;

    public function __construct(private readonly QrCodeService $qrCodes) {}

    /**
     * Record the student present, or return their existing present record if they scan again.
     *
     * @throws ValidationException when any rule fails; the message is meant for the student.
     */
    public function checkIn(
        User $student,
        string $code,
        float $latitude,
        float $longitude,
        float $accuracyMeters,
        bool $isMocked = false,
    ): AttendanceRecord {
        $session = $this->sessionFor($code);
        $this->ensureCheckInAllowed($session, $student);

        $existing = $session->attendanceRecords()->where('student_id', $student->id)->first();
        if ($existing) {
            return $existing->status === AttendanceStatus::Present
                ? $existing
                : $this->reject('code', 'Your instructor has already recorded your attendance for this lecture.');
        }

        $codeAge = $this->qrCodes->age($session, $code);
        if ($codeAge === null) {
            $this->reject('code', 'This QR code has expired. Scan the code on the screen again.');
        }

        $this->ensureNearRoom($session, $latitude, $longitude, $accuracyMeters, $isMocked);

        $flags = array_values(array_filter([
            $codeAge > 0 ? AttendanceFlag::TokenNearExpiry : null,
            $accuracyMeters > self::WEAK_ACCURACY_METERS ? AttendanceFlag::WeakLocationSignal : null,
            $this->isFarFromClass($session, $latitude, $longitude) ? AttendanceFlag::FarFromClass : null,
        ]));

        return AttendanceRecord::createOrFirst(
            ['lecture_session_id' => $session->id, 'student_id' => $student->id],
            [
                'status' => AttendanceStatus::Present,
                'source' => AttendanceSource::Scan,
                'recorded_at' => now(),
                'latitude' => $latitude,
                'longitude' => $longitude,
                'accuracy_meters' => $accuracyMeters,
                'flags' => $flags,
            ],
        );
    }

    private function sessionFor(string $code): LectureSession
    {
        $sessionId = $this->qrCodes->sessionIdFrom($code);
        $session = $sessionId ? LectureSession::with('room', 'section')->find($sessionId) : null;

        return $session ?? $this->reject('code', "This isn't a UniCheck attendance code.");
    }

    private function ensureCheckInAllowed(LectureSession $session, User $student): void
    {
        if (! $session->section->students()->whereKey($student->id)->exists()) {
            $this->reject('code', "You aren't enrolled in this class.");
        }

        if (! $session->isCheckInOpen()) {
            $this->reject('code', 'Check-in for this lecture is closed.');
        }
    }

    private function ensureNearRoom(
        LectureSession $session,
        float $latitude,
        float $longitude,
        float $accuracyMeters,
        bool $isMocked,
    ): void {
        if ($isMocked) {
            $this->reject('location', 'Turn off fake-location apps to check in.');
        }

        $room = $session->room;
        $distance = Geo::distanceMeters($latitude, $longitude, $room->latitude, $room->longitude);
        // Give the benefit of the doubt for GPS error, but only up to a limit, so a very
        // inaccurate reading from home can't pass.
        $allowedError = min($accuracyMeters, self::WEAK_ACCURACY_METERS);

        if ($distance - $allowedError > $room->radius_meters) {
            $this->reject('location', 'You need to be in the lecture room to check in.');
        }
    }

    private function isFarFromClass(LectureSession $session, float $latitude, float $longitude): bool
    {
        $others = $session->attendanceRecords()
            ->where('source', AttendanceSource::Scan)
            ->whereNotNull('latitude')
            ->get(['latitude', 'longitude']);

        if ($others->count() < self::MIN_CHECK_INS_FOR_CLASS_POSITION) {
            return false;
        }

        $distance = Geo::distanceMeters($latitude, $longitude, $others->median('latitude'), $others->median('longitude'));

        return $distance > self::FAR_FROM_CLASS_METERS;
    }

    private function reject(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
