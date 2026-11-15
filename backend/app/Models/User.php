<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

// A student, instructor or admin. `role` is "student", "instructor" or "admin".
class User extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $fillable = ['university_id', 'name', 'email', 'password', 'role', 'is_active', 'must_change_password'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'must_change_password' => 'boolean',
    ];

    // Creates an imported account. The first password is the university ID, and the
    // app makes the user choose a new one the first time they log in.
    public static function newAccount(string $universityId, string $name, string $role): User
    {
        return User::create([
            'university_id' => $universityId,
            'name' => $name,
            'password' => $universityId,
            'role' => $role,
            'must_change_password' => true,
        ]);
    }

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
