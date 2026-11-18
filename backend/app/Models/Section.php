<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// One class group of a course in a semester, taught by one instructor.
class Section extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id', 'number', 'semester', 'instructor_id', 'default_room_id',
        'building', 'room_number', 'latitude', 'longitude',
    ];

    // building, room_number, latitude, longitude: the section's own location, set by its
    // instructor (null until set). Students must be near this point to check in.
    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function defaultRoom()
    {
        return $this->belongsTo(Room::class, 'default_room_id');
    }

    public function students()
    {
        return $this->belongsToMany(User::class, 'enrollments', 'section_id', 'student_id')->withTimestamps();
    }

    public function lectureSessions()
    {
        return $this->hasMany(LectureSession::class);
    }

    public function excuses()
    {
        return $this->hasMany(Excuse::class);
    }

    // The lecture running right now, if any. A section has at most one.
    public function openSession()
    {
        return $this->hasOne(LectureSession::class)->where('status', 'open');
    }

    // The section's instructor and admins can start lectures and change attendance.
    public function canManage(User $user): bool
    {
        return $user->role === 'admin' || $this->instructor_id == $user->id;
    }

    public function hasStudent(User $user): bool
    {
        return $this->students()->where('users.id', $user->id)->exists();
    }
}
