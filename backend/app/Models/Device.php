<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// A student's phone. A student can have several, but a phone belongs to only one student.
class Device extends Model
{
    protected $fillable = ['student_id', 'device_uid', 'name', 'last_used_at'];

    protected $hidden = ['device_uid'];

    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    // The name of this phone's login token, so we can log the phone out when it is removed.
    public function tokenName(): string
    {
        return 'phone-'.$this->id;
    }
}
