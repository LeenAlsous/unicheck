<?php

namespace App\Enums;

/**
 * Signals that make a check-in worth an instructor's review.
 */
enum AttendanceFlag: string
{
    case WeakLocationSignal = 'weak_location_signal';
    case FarFromClass = 'far_from_class';
    case TokenNearExpiry = 'token_near_expiry';
    case RecentDeviceChange = 'recent_device_change';
}
