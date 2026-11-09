<?php

namespace Database\Factories;

use App\Enums\SessionStatus;
use App\Models\LectureSession;
use App\Models\Room;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LectureSession>
 */
class LectureSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'section_id' => Section::factory(),
            'room_id' => fn (array $attributes) => Section::find($attributes['section_id'])->default_room_id
                ?? Room::factory(),
            'status' => SessionStatus::Scheduled,
            'qr_step_seconds' => 10,
        ];
    }

    /**
     * Check-in is open for the next 15 minutes.
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SessionStatus::Open,
            'opened_at' => now(),
            'closes_at' => now()->addMinutes(15),
        ]);
    }
}
