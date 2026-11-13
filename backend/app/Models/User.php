<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

// A student, instructor or admin. `role` is "student", "instructor" or "admin".
class User extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $fillable = ['university_id', 'name', 'email', 'password', 'role', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    // Sections this user teaches (instructors).
    public function taughtSections()
    {
        return $this->hasMany(Section::class, 'instructor_id');
    }

    // Sections this user is enrolled in (students).
    public function enrolledSections()
    {
        return $this->belongsToMany(Section::class, 'enrollments', 'student_id', 'section_id')->withTimestamps();
    }

    // Phones registered by this user (students).
    public function devices()
    {
        return $this->hasMany(Device::class, 'student_id');
    }
}
