<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\SectionResource;
use App\Http\Resources\StudentResource;
use App\Models\Section;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * Class sections: the home screen list for both roles.
 */
class SectionController extends Controller
{
    /**
     * My sections.
     *
     * Instructors get the sections they teach, students the sections they're enrolled in,
     * admins every section. `open_session` is set while check-in is running.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $sections = Section::query()
            ->with(['course', 'instructor', 'defaultRoom', 'openSession.room'])
            ->withCount('students')
            ->when($user->role === UserRole::Instructor, fn (Builder $q) => $q->where('instructor_id', $user->id))
            ->when($user->role === UserRole::Student, fn (Builder $q) => $q->whereHas(
                'students',
                fn (Builder $students) => $students->whereKey($user->id),
            ))
            ->orderByDesc('semester')
            ->orderBy('id')
            ->get();

        return SectionResource::collection($sections);
    }

    /**
     * One section.
     */
    public function show(Section $section): SectionResource
    {
        Gate::authorize('view', $section);

        $section->load(['course', 'instructor', 'defaultRoom', 'openSession.room'])->loadCount('students');

        return new SectionResource($section);
    }

    /**
     * Students enrolled in a section.
     */
    public function students(Section $section): AnonymousResourceCollection
    {
        Gate::authorize('manage', $section);

        return StudentResource::collection($section->students()->orderBy('university_id')->get());
    }
}
