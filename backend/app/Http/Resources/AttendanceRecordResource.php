<?php

namespace App\Http\Resources;

use App\Models\AttendanceRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One student's attendance for one lecture.
 *
 * @mixin AttendanceRecord
 */
class AttendanceRecordResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lecture_session_id' => $this->lecture_session_id,
            'student_id' => $this->student_id,
            'status' => $this->status,
            'source' => $this->source,
            'recorded_at' => $this->recorded_at,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'accuracy_meters' => $this->accuracy_meters,
            /** Reasons the instructor may want to double-check this check-in. */
            'flags' => $this->flags ?? [],
            'note' => $this->note,
        ];
    }
}
