<?php

namespace App\Models;

use App\Enums\SessionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One lecture of a section, with its own check-in window and QR secret.
 */
#[Fillable(['section_id', 'room_id', 'status', 'opened_at', 'closes_at', 'closed_at', 'qr_step_seconds'])]
#[Hidden(['qr_secret'])]
class LectureSession extends Model
{
    /**
     * Give every new session its own random key for signing QR codes.
     */
    protected static function booted(): void
    {
        static::creating(function (LectureSession $session) {
            $session->qr_secret ??= base64_encode(random_bytes(32));
            $session->status ??= SessionStatus::Scheduled;
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SessionStatus::class,
            'opened_at' => 'datetime',
            'closes_at' => 'datetime',
            'closed_at' => 'datetime',
            'qr_secret' => 'encrypted',
            'qr_step_seconds' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Section, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * @return BelongsTo<Room, $this>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * @return HasMany<AttendanceRecord, $this>
     */
    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }
}
