<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// One lecture of a section. `status` is "scheduled", "open" or "closed".
class LectureSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'section_id', 'room_id', 'status', 'opened_at', 'closes_at', 'closed_at', 'qr_secret', 'qr_step_seconds',
    ];

    // The QR secret is only sent to the instructor (see LectureSessionController).
    protected $hidden = ['qr_secret'];

    protected $casts = [
        'opened_at' => 'datetime',
        'closes_at' => 'datetime',
        'closed_at' => 'datetime',
        'qr_secret' => 'encrypted',
    ];

    // Always include "check_in_open" in the JSON.
    protected $appends = ['check_in_open'];

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    // Students can check in while the session is open and the check-in time hasn't ended.
    public function getCheckInOpenAttribute(): bool
    {
        return $this->status === 'open' && $this->closes_at !== null && now()->lte($this->closes_at);
    }
}
