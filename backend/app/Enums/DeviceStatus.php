<?php

namespace App\Enums;

/**
 * Approval state of a student's registered phone.
 */
enum DeviceStatus: string
{
    /** Waiting for an admin to approve a device change. */
    case Pending = 'pending';

    /** The device the student checks in with. */
    case Active = 'active';

    /** Replaced or blocked; check-ins from it are rejected. */
    case Revoked = 'revoked';
}
