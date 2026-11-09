<?php

namespace App\Enums;

/**
 * A student's attendance result for one session.
 */
enum AttendanceStatus: string
{
    case Present = 'present';
    case Absent = 'absent';
    case Excused = 'excused';
}
