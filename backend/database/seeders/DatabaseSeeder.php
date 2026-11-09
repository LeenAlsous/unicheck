<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Room;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Fake users, rooms and courses for development. Run with `php artisan migrate:fresh --seed`.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Password for every seeded account. Development only.
     */
    public const DEV_PASSWORD = 'UniCheck@2026';

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->user('A0001', 'System Admin', UserRole::Admin);
        $instructor1 = $this->user('I1001', 'Dr. Instructor One', UserRole::Instructor);
        $instructor2 = $this->user('I1002', 'Dr. Instructor Two', UserRole::Instructor);
        $students = collect(range(1, 12))
            ->map(fn (int $i) => $this->user(sprintf('S%04d', $i), "Student {$i}", UserRole::Student));

        // Placeholder coordinates: replace with the real rooms.
        $roomA = Room::create(['building' => 'IT Building', 'name' => '101', 'latitude' => 31.9539, 'longitude' => 35.9106]);
        $roomB = Room::create(['building' => 'IT Building', 'name' => '204', 'latitude' => 31.9541, 'longitude' => 35.9110]);

        $databases = Course::create(['code' => 'CS330', 'name' => 'Database Systems', 'max_absences' => 6]);
        $dataStructures = Course::create(['code' => 'CS201', 'name' => 'Data Structures', 'max_absences' => 6]);

        $sectionA = Section::create([
            'course_id' => $databases->id, 'number' => '1', 'semester' => '2026-1',
            'instructor_id' => $instructor1->id, 'default_room_id' => $roomA->id,
        ]);
        $sectionB = Section::create([
            'course_id' => $dataStructures->id, 'number' => '1', 'semester' => '2026-1',
            'instructor_id' => $instructor2->id, 'default_room_id' => $roomB->id,
        ]);

        // Students 1–8 in databases, 5–12 in data structures (5–8 take both).
        $sectionA->students()->attach($students->slice(0, 8)->pluck('id'));
        $sectionB->students()->attach($students->slice(4)->pluck('id'));
    }

    private function user(string $universityId, string $name, UserRole $role): User
    {
        return User::create([
            'university_id' => $universityId,
            'name' => $name,
            'password' => self::DEV_PASSWORD,
            'role' => $role,
        ]);
    }
}
