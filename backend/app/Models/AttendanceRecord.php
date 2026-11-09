<?php

namespace App\Models;

use App\Enums\AttendanceFlag;
use App\Enums\AttendanceSource;
use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A student's attendance for one lecture session.
 */
#[Fillable([
    'lecture_session_id', 'student_id', 'status', 'source', 'recorded_at',
    'latitude', 'longitude', 'accuracy_meters', 'device_id', 'flags', 'note',
])]
class AttendanceRecord extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'status' => AttendanceStatus::class,
            'source' => AttendanceSource::class,
            'recorded_at' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
            'accuracy_meters' => 'float',
            'flags' => AsEnumCollection::of(AttendanceFlag::class),
        ];
    }

    /**
     * @return BelongsTo<LectureSession, $this>
     */
    public function lectureSession(): BelongsTo
    {
        return $this->belongsTo(LectureSession::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * @return BelongsTo<Device, $this>
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
