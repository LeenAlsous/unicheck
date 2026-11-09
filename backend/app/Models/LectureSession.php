<?php

namespace App\Models;

use App\Enums\AttendanceSource;
use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use Database\Factories\LectureSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * One lecture of a section, with its own check-in window and QR secret.
 */
#[Fillable(['section_id', 'room_id', 'status', 'opened_at', 'closes_at', 'closed_at', 'qr_step_seconds'])]
#[Hidden(['qr_secret'])]
class LectureSession extends Model
{
    /** @use HasFactory<LectureSessionFactory> */
    use HasFactory;

    /**
     * Give every new session its own random key for signing QR codes.
     */
    protected static function booted(): void
    {
        static::creating(function (LectureSession $session) {
            $session->qr_secret ??= base64_encode(random_bytes(32));
            $session->status ??= SessionStatus::Scheduled;
            $session->qr_step_seconds ??= 10;
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

    /**
     * Whether students can check in right now.
     */
    public function isCheckInOpen(): bool
    {
        return $this->status === SessionStatus::Open
            && $this->closes_at !== null
            && now()->lte($this->closes_at);
    }

    /**
     * End the session and mark every enrolled student who didn't check in as absent.
     */
    public function close(): void
    {
        DB::transaction(function () {
            $now = now();
            $this->update([
                'status' => SessionStatus::Closed,
                'closed_at' => $now,
                'closes_at' => $this->closes_at?->min($now) ?? $now,
            ]);

            $this->section->students()
                ->whereNotIn('users.id', $this->attendanceRecords()->select('student_id'))
                ->pluck('users.id')
                ->each(fn (int $studentId) => $this->attendanceRecords()->create([
                    'student_id' => $studentId,
                    'status' => AttendanceStatus::Absent,
                    'source' => AttendanceSource::Auto,
                    'recorded_at' => $now,
                ]));
        });
    }
}
