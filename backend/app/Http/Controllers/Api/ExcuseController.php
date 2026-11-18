<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Excuse;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExcuseController extends Controller
{
    /**
     * Excuses of a section.
     *
     * Instructors get every student's excuses (pending ones first); students get their own.
     */
    public function index(Request $request, Section $section)
    {
        $user = $request->user();
        $query = $section->excuses()->with('student:id,university_id,name');

        if ($section->canManage($user)) {
            $query->orderByRaw("status = 'pending' desc");
        } elseif ($section->hasStudent($user)) {
            $query->where('student_id', $user->id);
        } else {
            abort(403);
        }

        return $query->orderByDesc('date')->orderByDesc('id')->get();
    }

    /**
     * Submit an excuse (students).
     *
     * For missing the lectures of this section on `date`, with a photo of the document,
     * e.g. a medical report or an exam schedule. `reason` is "medical", "exam" or "other".
     */
    public function store(Request $request, Section $section)
    {
        if (! $section->hasStudent($request->user())) {
            abort(403);
        }

        $request->validate([
            'date' => 'required|date',
            'reason' => 'required|in:medical,exam,other',
            'note' => 'nullable|string|max:500',
            'photo' => 'required|image|max:5120', // up to 5 MB
        ]);

        $excuse = $section->excuses()->create([
            'student_id' => $request->user()->id,
            'date' => $request->date,
            'reason' => $request->reason,
            'note' => $request->note,
            'photo_path' => $request->file('photo')->store('excuses'), // storage/app/private/excuses
        ]);

        return response()->json($excuse->fresh(), 201);
    }

    /**
     * The photo of an excuse (the student who sent it, or the section's instructor).
     */
    public function photo(Request $request, Excuse $excuse)
    {
        $user = $request->user();

        if ($excuse->student_id != $user->id && ! $excuse->section->canManage($user)) {
            abort(403);
        }

        return Storage::response($excuse->photo_path);
    }

    /**
     * Approve or reject an excuse (instructors).
     *
     * Approving marks the student excused in the section's lectures on that day, unless they
     * were present. Lectures that end later that day mark them excused instead of absent.
     */
    public function review(Request $request, Excuse $excuse)
    {
        if (! $excuse->section->canManage($request->user())) {
            abort(403);
        }

        $request->validate(['status' => 'required|in:approved,rejected']);

        if ($excuse->status !== 'pending') {
            abort(422, 'This excuse was already reviewed.');
        }

        $excuse->update([
            'status' => $request->status,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        if ($excuse->status === 'approved') {
            $lectures = $excuse->section->lectureSessions()->whereDate('opened_at', $excuse->date)->get();

            foreach ($lectures as $lecture) {
                $record = $lecture->attendanceRecords()->where('student_id', $excuse->student_id)->first();

                if ($record && $record->status === 'present') {
                    continue; // they came after all
                }

                AttendanceRecord::updateOrCreate(
                    ['lecture_session_id' => $lecture->id, 'student_id' => $excuse->student_id],
                    ['status' => 'excused', 'source' => 'manual', 'recorded_at' => now(), 'note' => 'Excuse approved'],
                );
            }
        }

        return $excuse;
    }
}
