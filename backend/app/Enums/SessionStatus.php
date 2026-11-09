<?php

namespace App\Enums;

/**
 * Lifecycle of a lecture session's check-in window.
 */
enum SessionStatus: string
{
    /** Created, but check-in has not been opened yet. */
    case Scheduled = 'scheduled';

    /** The rotating QR is live and students can check in. */
    case Open = 'open';

    /** Check-in is over; records can only be changed manually. */
    case Closed = 'closed';
}
