<?php

namespace App\Enums;

/**
 * How an attendance record was created.
 */
enum AttendanceSource: string
{
    /** The student scanned the QR in the app. */
    case Scan = 'scan';

    /** An instructor set or corrected it. */
    case Manual = 'manual';
}
