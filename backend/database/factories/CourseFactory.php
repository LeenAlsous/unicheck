<?php

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'CS'.fake()->unique()->numberBetween(100, 999),
            'name' => ucwords(fake()->words(2, true)),
            'max_absences' => 6,
        ];
    }
}
