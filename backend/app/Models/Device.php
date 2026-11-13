<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// A student's phone (not used yet). `status` is "pending", "active" or "revoked".
class Device extends Model
{
    protected $fillable = ['student_id', 'public_key', 'platform', 'model', 'status', 'approved_at'];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
