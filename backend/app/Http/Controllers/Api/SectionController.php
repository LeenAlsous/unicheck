<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\User;
use App\Services\SpreadsheetReader;
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

    /**
     * Add students to a section from an Excel or CSV file.
     *
     * The first row must have the headings `University ID` and `Name`. Students who don't have
     * an account yet get one; their first password is their university ID, and the app asks
     * them to change it. Returns how many were added and the rows that were skipped, and why.
     */
    public function importStudents(Request $request, Section $section)
    {
        if (! $section->canManage($request->user())) {
            abort(403);
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:2048',
        ]);

        $file = $request->file('file');
        $sheet = SpreadsheetReader::read($file->getRealPath(), $file->getClientOriginalExtension());

        if (! in_array('university id', $sheet['headings']) || ! in_array('name', $sheet['headings'])) {
            abort(422, 'The first row of the file must have the headings "University ID" and "Name".');
        }

        $added = 0;
        $alreadyInClass = 0;
        $newAccounts = 0;
        $problems = [];

        foreach ($sheet['rows'] as $rowNumber => $row) {
            $universityId = $row['university id'];
            $name = $row['name'];

            if ($universityId === '') {
                $problems[] = "Row $rowNumber: no university ID.";

                continue;
            }

            $student = User::where('university_id', $universityId)->first();

            if ($student && $student->role !== 'student') {
                $problems[] = "Row $rowNumber: $universityId is not a student account.";

                continue;
            }

            if (! $student) {
                if ($name === '') {
                    $problems[] = "Row $rowNumber: $universityId has no account yet, so a name is needed.";

                    continue;
                }
                $student = User::newAccount($universityId, $name, 'student');
                $newAccounts++;
            }

            if ($section->hasStudent($student)) {
                $alreadyInClass++;
            } else {
                $section->students()->attach($student->id);
                $added++;
            }
        }

        return [
            'added' => $added,
            'already_in_class' => $alreadyInClass,
            'new_accounts' => $newAccounts,
            'problems' => $problems,
        ];
    }
}
