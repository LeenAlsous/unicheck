<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Section;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    /**
     * My sections (the home screen).
     *
     * Instructors get the sections they teach, students the ones they're enrolled in, admins all.
     * `open_session` is set while a lecture is running.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Section::with(['course', 'instructor', 'defaultRoom', 'openSession'])->withCount('students');

        if ($user->role === 'instructor') {
            $query->where('instructor_id', $user->id);
        } elseif ($user->role === 'student') {
            $query->whereHas('students', function ($students) use ($user) {
                $students->where('users.id', $user->id);
            });
        }

        return $query->orderBy('id')->get();
    }

    /**
     * One section.
     */
    public function show(Request $request, Section $section)
    {
        $user = $request->user();

        if (! $section->canManage($user) && ! $section->hasStudent($user)) {
            abort(403);
        }

        return $section->load(['course', 'instructor', 'defaultRoom', 'openSession'])->loadCount('students');
    }

    /**
     * Students enrolled in a section.
     */
    public function students(Request $request, Section $section)
    {
        if (! $section->canManage($request->user())) {
            abort(403);
        }

        return $section->students()->orderBy('university_id')->get(['users.id', 'university_id', 'name']);
    }
}
