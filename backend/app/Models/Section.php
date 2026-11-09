<?php

namespace App\Models;

use App\Enums\SessionStatus;
use Database\Factories\SectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * One class group of a course in a semester, taught by one instructor.
 */
#[Fillable(['course_id', 'number', 'semester', 'instructor_id', 'default_room_id'])]
class Section extends Model
{
    /** @use HasFactory<SectionFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    /**
     * @return BelongsTo<Room, $this>
     */
    public function defaultRoom(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'default_room_id');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'enrollments', 'section_id', 'student_id')->withTimestamps();
    }

    /**
     * @return HasMany<LectureSession, $this>
     */
    public function lectureSessions(): HasMany
    {
        return $this->hasMany(LectureSession::class);
    }

    /**
     * The session that is open right now, if any. A section has at most one.
     *
     * @return HasOne<LectureSession, $this>
     */
    public function openSession(): HasOne
    {
        return $this->hasOne(LectureSession::class)->ofMany(
            ['id' => 'max'],
            fn (Builder $query) => $query->where('status', SessionStatus::Open),
        );
    }
}
