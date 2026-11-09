<?php

namespace App\Enums;

/**
 * What a user is allowed to do in the system.
 */
enum UserRole: string
{
    case Student = 'student';
    case Instructor = 'instructor';
    case Admin = 'admin';
}
