<?php

namespace Database\Factories;

use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'building' => 'IT Building',
            'name' => (string) fake()->unique()->numberBetween(100, 999),
            'latitude' => 31.9539,
            'longitude' => 35.9106,
            'radius_meters' => 75,
        ];
    }
}
