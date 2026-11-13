<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// A student's attendance for one lecture.
// status: "present", "absent" or "excused"
// source: "scan" (student scanned the QR), "manual" (instructor) or "auto" (absent when the lecture closed)
// flags:  reasons the instructor may want to double-check a scan, e.g. ["weak_location_signal"]
class AttendanceRecord extends Model
{
    protected $fillable = [
        'lecture_session_id', 'student_id', 'status', 'source', 'recorded_at',
        'latitude', 'longitude', 'accuracy_meters', 'device_id', 'flags', 'note',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'accuracy_meters' => 'float',
        'flags' => 'array',
    ];

    public function lectureSession()
    {
        return $this->belongsTo(LectureSession::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
