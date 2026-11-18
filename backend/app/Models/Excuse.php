<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// A student's excuse for missing the lectures of a section on one day.
// reason: "medical", "exam" or "other"
// status: "pending", "approved" or "rejected"
class Excuse extends Model
{
    protected $fillable = [
        'section_id', 'student_id', 'date', 'reason', 'note', 'photo_path', 'status', 'reviewed_by', 'reviewed_at',
    ];

    // The photo is downloaded from its own endpoint, so the file's path isn't sent.
    protected $hidden = ['photo_path'];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'reviewed_at' => 'datetime',
    ];

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
