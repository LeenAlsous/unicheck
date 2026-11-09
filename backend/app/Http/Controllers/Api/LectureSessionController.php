<?php

namespace App\Http\Controllers\Api;

use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\OpenSessionRequest;
use App\Http\Resources\LectureSessionResource;
use App\Models\LectureSession;
use App\Models\Section;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Starting and ending lectures (instructors).
 */
class LectureSessionController extends Controller
{
    /**
     * Past and current sessions of a section, newest first.
     */
    public function index(Section $section): AnonymousResourceCollection
    {
        Gate::authorize('manage', $section);

        $sessions = $section->lectureSessions()
            ->with('room')
            ->withCount([
                'attendanceRecords as present_count' => fn (Builder $q) => $q->where('status', AttendanceStatus::Present),
                'attendanceRecords as absent_count' => fn (Builder $q) => $q->where('status', AttendanceStatus::Absent),
            ])
            ->latest('id')
            ->get();

        return LectureSessionResource::collection($sessions);
    }

    /**
     * Start a session.
     *
     * Opens check-in right away. The response includes `qr_secret` and `server_time_ms`,
     * which the instructor app needs to show the rotating QR code.
     */
    public function store(OpenSessionRequest $request, Section $section): JsonResponse
    {
        Gate::authorize('manage', $section);

        abort_if($section->openSession()->exists(), 409, 'This section already has an open session. Close it first.');

        $roomId = $request->integer('room_id') ?: $section->default_room_id;
        if (! $roomId) {
            throw ValidationException::withMessages(['room_id' => 'Choose a room: this section has no usual room.']);
        }

        $session = $section->lectureSessions()->create([
            'room_id' => $roomId,
            'status' => SessionStatus::Open,
            'opened_at' => now(),
            'closes_at' => now()->addMinutes($request->integer('duration_minutes') ?: 15),
        ]);

        return (new LectureSessionResource($session->load('room')))
            ->withQrSecret()
            ->response()
            ->setStatusCode(201);
    }

    /**
     * One session, with its QR secret.
     *
     * Use this to show the QR again, for example after the app restarts or on the lecture-room PC.
     */
    public function show(LectureSession $session): LectureSessionResource
    {
        Gate::authorize('manage', $session->section);

        return (new LectureSessionResource($session->load('room')))->withQrSecret();
    }

    /**
     * End a session.
     *
     * Stops check-in and marks every enrolled student who didn't check in as absent.
     * Closing a session that is already closed does nothing.
     */
    public function close(LectureSession $session): LectureSessionResource
    {
        Gate::authorize('manage', $session->section);

        if ($session->status !== SessionStatus::Closed) {
            $session->close();
        }

        return new LectureSessionResource($session->load('room'));
    }
}
