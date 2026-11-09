<?php

namespace App\Support;

/**
 * Distance between GPS coordinates.
 */
final class Geo
{
    private const EARTH_RADIUS_METERS = 6_371_000;

    /**
     * Straight-line distance in meters between two points (haversine formula).
     */
    public static function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * self::EARTH_RADIUS_METERS * asin(min(1, sqrt($a)));
    }
}
