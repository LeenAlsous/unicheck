<?php

namespace App\Http\Controllers\Api;

use App\Enums\AttendanceSource;
use App\Enums\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\MarkAttendanceRequest;
use App\Http\Resources\AttendanceRecordResource;
use App\Http\Resources\LectureSessionResource;
use App\Http\Resources\StudentResource;
use App\Models\AttendanceRecord;
use App\Models\LectureSession;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * The live attendance list and manual changes (instructors).
 */
class AttendanceController extends Controller
{
    /**
     * Live attendance list.
     *
     * Every enrolled student with their attendance, or `attendance: null` if they haven't
     * checked in yet. The instructor app polls this every few seconds while the session is open.
     */
    public function index(LectureSession $session): array
    {
        Gate::authorize('manage', $session->section);

        $records = $session->attendanceRecords()->get()->keyBy('student_id');
        $students = $session->section->students()->orderBy('university_id')->get();
        $counts = $records->countBy(fn (AttendanceRecord $record) => $record->status->value);

        return [
            'session' => new LectureSessionResource($session->load('room')),
            'summary' => [
                'enrolled' => $students->count(),
                'present' => $counts->get(AttendanceStatus::Present->value, 0),
                'absent' => $counts->get(AttendanceStatus::Absent->value, 0),
                'excused' => $counts->get(AttendanceStatus::Excused->value, 0),
                'not_checked_in' => $students->count() - $records->count(),
            ],
            'students' => $students->map(fn (User $student) => [
                'student' => new StudentResource($student),
                'attendance' => $records->has($student->id) ? new AttendanceRecordResource($records->get($student->id)) : null,
            ]),
        ];
    }

    /**
     * Set one student's attendance.
     *
     * For corrections: marking someone present who couldn't scan, absent, or excused with a note.
     * Works while the session is open and after it is closed.
     */
    public function update(MarkAttendanceRequest $request, LectureSession $session, User $student): AttendanceRecordResource
    {
        Gate::authorize('manage', $session->section);

        abort_unless($session->section->students()->whereKey($student->id)->exists(), 404, 'This student is not in this section.');

        $record = AttendanceRecord::updateOrCreate(
            ['lecture_session_id' => $session->id, 'student_id' => $student->id],
            [
                'status' => $request->enum('status', AttendanceStatus::class),
                'source' => AttendanceSource::Manual,
                'recorded_at' => now(),
                'note' => $request->input('note'),
            ],
        );

        return new AttendanceRecordResource($record);
    }
}
