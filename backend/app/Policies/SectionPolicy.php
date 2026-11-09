<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Section;
use App\Models\User;

/**
 * Who can see and run a section.
 */
class SectionPolicy
{
    /**
     * Admins, the section's instructor and its enrolled students.
     */
    public function view(User $user, Section $section): bool
    {
        return match ($user->role) {
            UserRole::Admin => true,
            UserRole::Instructor => $section->instructor()->is($user),
            UserRole::Student => $section->students()->whereKey($user->id)->exists(),
        };
    }

    /**
     * Open and close sessions, see the roster and change attendance: admins and the section's instructor.
     */
    public function manage(User $user, Section $section): bool
    {
        return $user->role === UserRole::Admin || $section->instructor()->is($user);
    }
}
