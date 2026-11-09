<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Room;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Section>
 */
class SectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'number' => '1',
            'semester' => '2026-1',
            'instructor_id' => User::factory()->instructor(),
            'default_room_id' => Room::factory(),
        ];
    }
}
