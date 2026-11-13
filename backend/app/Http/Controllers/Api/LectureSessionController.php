<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LectureSession;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LectureSessionController extends Controller
{
    /**
     * Past and current lectures of a section, newest first.
     */
    public function index(Request $request, Section $section)
    {
        if (! $section->canManage($request->user())) {
            abort(403);
        }

        return $section->lectureSessions()
            ->with('room')
            ->withCount([
                'attendanceRecords as present_count' => fn ($q) => $q->where('status', 'present'),
                'attendanceRecords as absent_count' => fn ($q) => $q->where('status', 'absent'),
            ])
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Start a lecture.
     *
     * Opens check-in right away (15 minutes by default). The response has `qr_secret` and
     * `server_time_ms`, which the instructor app needs to show the QR code.
     */
    public function store(Request $request, Section $section)
    {
        if (! $section->canManage($request->user())) {
            abort(403);
        }

        $request->validate([
            'room_id' => 'nullable|integer|exists:rooms,id',
            'duration_minutes' => 'nullable|integer|min:1|max:180',
        ]);

        if ($section->openSession()->exists()) {
            abort(409, 'This section already has an open session. Close it first.');
        }

        $roomId = $request->room_id ?? $section->default_room_id;
        if (! $roomId) {
            throw ValidationException::withMessages(['room_id' => 'Choose a room: this section has no usual room.']);
        }

        $session = $section->lectureSessions()->create([
            'room_id' => $roomId,
            'status' => 'open',
            'opened_at' => now(),
            'closes_at' => now()->addMinutes($request->duration_minutes ?? 15),
            'qr_secret' => Str::random(40),
            'qr_step_seconds' => 10,
        ]);

        return response()->json($this->withQrSecret($session), 201);
    }

    /**
     * One lecture, with its QR secret (to show the QR again, e.g. on the lecture-room PC).
     */
    public function show(Request $request, LectureSession $session)
    {
        if (! $session->section->canManage($request->user())) {
            abort(403);
        }

        return $this->withQrSecret($session);
    }

    /**
     * End a lecture.
     *
     * Stops check-in and marks every student who didn't check in as absent.
     */
    public function close(Request $request, LectureSession $session)
    {
        if (! $session->section->canManage($request->user())) {
            abort(403);
        }

        if ($session->status !== 'closed') {
            $session->update([
                'status' => 'closed',
                'closed_at' => now(),
                'closes_at' => $session->closes_at && $session->closes_at->isPast() ? $session->closes_at : now(),
            ]);

            $alreadyRecorded = $session->attendanceRecords()->pluck('student_id');

            foreach ($session->section->students as $student) {
                if (! $alreadyRecorded->contains($student->id)) {
                    $session->attendanceRecords()->create([
                        'student_id' => $student->id,
                        'status' => 'absent',
                        'source' => 'auto',
                        'recorded_at' => now(),
                    ]);
                }
            }
        }

        return $session->load('room');
    }

    // The session as JSON plus what the instructor app needs to draw the QR code.
    private function withQrSecret(LectureSession $session): array
    {
        $session->load('room');

        return array_merge($session->toArray(), [
            'qr_secret' => $session->qr_secret,
            'server_time_ms' => now()->getTimestampMs(), // the app uses this to fix a wrong phone clock
        ]);
    }
}
