<?php

namespace Tests\Feature;

use App\Models\LectureSession;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_sees_only_the_sections_they_teach(): void
    {
        $mine = Section::factory()->create();
        Section::factory()->create();
        Sanctum::actingAs($mine->instructor);

        $this->getJson('/api/sections')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $mine->id)
            ->assertJsonPath('0.course.code', $mine->course->code)
            ->assertJsonPath('0.default_room.id', $mine->default_room_id)
            ->assertJsonPath('0.students_count', 0)
            ->assertJsonPath('0.open_session', null);
    }

    public function test_student_sees_only_the_sections_they_are_enrolled_in(): void
    {
        $student = User::factory()->create();
        $enrolled = Section::factory()->create();
        $enrolled->students()->attach($student);
        Section::factory()->create();
        Sanctum::actingAs($student);

        $this->getJson('/api/sections')->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $enrolled->id);
    }

    public function test_admin_sees_every_section(): void
    {
        Section::factory()->count(2)->create();
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson('/api/sections')->assertOk()->assertJsonCount(2);
    }

    public function test_students_can_only_open_their_own_sections(): void
    {
        $student = User::factory()->create();
        $enrolled = Section::factory()->create();
        $enrolled->students()->attach($student);
        $other = Section::factory()->create();
        Sanctum::actingAs($student);

        $this->getJson("/api/sections/{$enrolled->id}")->assertOk();
        $this->getJson("/api/sections/{$other->id}")->assertForbidden();
    }

    public function test_only_the_instructor_sees_the_class_list(): void
    {
        $section = Section::factory()->create();
        $students = User::factory()->count(2)->create();
        $section->students()->attach($students);

        Sanctum::actingAs($section->instructor);
        $this->getJson("/api/sections/{$section->id}/students")->assertOk()->assertJsonCount(2);

        Sanctum::actingAs(User::factory()->instructor()->create());
        $this->getJson("/api/sections/{$section->id}/students")->assertForbidden();

        Sanctum::actingAs($students->first());
        $this->getJson("/api/sections/{$section->id}/students")->assertForbidden();
    }

    public function test_students_see_how_many_absences_they_have(): void
    {
        $student = User::factory()->create();
        $section = Section::factory()->create();
        $section->students()->attach($student);
        foreach (['absent', 'absent', 'excused', 'present'] as $status) {
            $lecture = LectureSession::factory()->for($section)->create();
            $lecture->attendanceRecords()->create([
                'student_id' => $student->id, 'status' => $status, 'source' => 'manual', 'recorded_at' => now(),
            ]);
        }
        // An absence in another course doesn't count.
        $other = LectureSession::factory()->create();
        $other->section->students()->attach($student);
        $other->attendanceRecords()->create([
            'student_id' => $student->id, 'status' => 'absent', 'source' => 'auto', 'recorded_at' => now(),
        ]);

        Sanctum::actingAs($student);
        $this->getJson('/api/sections')
            ->assertOk()
            ->assertJsonPath('0.my_absences', 2)
            ->assertJsonPath('0.course.max_absences', 6)
            ->assertJsonPath('1.my_absences', 1);

        Sanctum::actingAs($section->instructor);
        $this->getJson('/api/sections')->assertOk()->assertJsonMissingPath('0.my_absences');
    }
}
