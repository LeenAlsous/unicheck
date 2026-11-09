<?php

namespace App\Http\Resources;

use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A class section with its course, instructor and open session.
 *
 * @mixin Section
 */
class SectionResource extends JsonResource
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
            'number' => $this->number,
            'semester' => $this->semester,
            'course' => [
                'id' => $this->course->id,
                'code' => $this->course->code,
                'name' => $this->course->name,
                'max_absences' => $this->course->max_absences,
            ],
            'instructor' => [
                'id' => $this->instructor->id,
                'name' => $this->instructor->name,
            ],
            'default_room' => new RoomResource($this->whenLoaded('defaultRoom')),
            'students_count' => $this->whenCounted('students'),
            /** The session students can check in to right now, or null. */
            'open_session' => new LectureSessionResource($this->whenLoaded('openSession')),
        ];
    }
}
