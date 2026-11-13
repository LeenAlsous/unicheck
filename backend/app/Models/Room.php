<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// A lecture room. Students must be within `radius_meters` of it to check in.
class Room extends Model
{
    use HasFactory;

    protected $fillable = ['building', 'name', 'latitude', 'longitude', 'radius_meters'];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];
}
