<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\LectureSession;
use App\Models\User;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    /**
     * Live attendance list.
     *
     * Every student in the section with their attendance, or `attendance: null` if they
     * haven't checked in yet. The instructor app calls this every few seconds.
     */
    public function index(Request $request, LectureSession $session)
    {
        if (! $session->section->canManage($request->user())) {
            abort(403);
        }

        $records = $session->attendanceRecords()->get()->keyBy('student_id');
        $students = $session->section->students()->orderBy('university_id')->get();

        $list = [];
        foreach ($students as $student) {
            $list[] = [
                'student' => $student->only(['id', 'university_id', 'name']),
                'attendance' => $records->get($student->id),
            ];
        }

        return [
            'session' => $session->load('room'),
            'summary' => [
                'enrolled' => $students->count(),
                'present' => $records->where('status', 'present')->count(),
                'absent' => $records->where('status', 'absent')->count(),
                'excused' => $records->where('status', 'excused')->count(),
                'not_checked_in' => $students->count() - $records->count(),
            ],
            'students' => $list,
        ];
    }

    /**
     * Set one student's attendance by hand.
     *
     * For example: present when they couldn't scan, or excused with a note.
     */
    public function update(Request $request, LectureSession $session, User $student)
    {
        if (! $session->section->canManage($request->user())) {
            abort(403);
        }

        $request->validate([
            'status' => 'required|in:present,absent,excused',
            'note' => 'nullable|string|max:500',
        ]);

        if (! $session->section->hasStudent($student)) {
            abort(404, 'This student is not in this section.');
        }

        return AttendanceRecord::updateOrCreate(
            ['lecture_session_id' => $session->id, 'student_id' => $student->id],
            [
                'status' => $request->status,
                'source' => 'manual',
                'recorded_at' => now(),
                'note' => $request->note,
            ],
        );
    }
}
