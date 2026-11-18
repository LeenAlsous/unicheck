<?php

namespace Tests\Feature;

use App\Models\Excuse;
use App\Models\LectureSession;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExcuseTest extends TestCase
{
    use RefreshDatabase;

    private Section $section;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->travelTo(Carbon::parse('2026-11-18 10:00:00'));
        $this->section = Section::factory()->create();
        $this->student = User::factory()->create();
        $this->section->students()->attach($this->student);
    }

    private function submit(array $overrides = [])
    {
        return $this->post("/api/sections/{$this->section->id}/excuses", array_merge([
            'date' => '2026-11-18',
            'reason' => 'medical',
            'note' => 'Flu',
            'photo' => UploadedFile::fake()->image('report.jpg'),
        ], $overrides), ['Accept' => 'application/json']);
    }

    private function excuse(string $status = 'pending'): Excuse
    {
        return $this->section->excuses()->create([
            'student_id' => $this->student->id,
            'date' => '2026-11-18',
            'reason' => 'exam',
            'photo_path' => UploadedFile::fake()->image('schedule.jpg')->store('excuses'),
            'status' => $status,
        ]);
    }

    public function test_student_submits_an_excuse_with_a_photo(): void
    {
        Sanctum::actingAs($this->student);

        $this->submit()
            ->assertCreated()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('reason', 'medical')
            ->assertJsonPath('date', '2026-11-18')
            ->assertJsonMissingPath('photo_path');

        $excuse = Excuse::first();
        Storage::disk('local')->assertExists($excuse->photo_path);
    }

    public function test_excuse_needs_a_photo_and_a_valid_reason(): void
    {
        Sanctum::actingAs($this->student);

        $this->submit(['photo' => null])->assertJsonValidationErrors('photo');
        $this->submit(['reason' => 'tired'])->assertJsonValidationErrors('reason');
        $this->submit(['photo' => UploadedFile::fake()->create('report.exe', 10)])->assertJsonValidationErrors('photo');
    }

    public function test_students_outside_the_section_cannot_submit(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->submit()->assertForbidden();
    }

    public function test_students_see_only_their_own_excuses_and_instructors_see_all(): void
    {
        $this->excuse();
        $classmate = User::factory()->create();
        $this->section->students()->attach($classmate);
        $this->section->excuses()->create([
            'student_id' => $classmate->id, 'date' => '2026-11-18', 'reason' => 'other', 'photo_path' => 'x.jpg',
        ]);

        Sanctum::actingAs($this->student);
        $this->getJson("/api/sections/{$this->section->id}/excuses")->assertOk()->assertJsonCount(1);

        Sanctum::actingAs($this->section->instructor);
        $this->getJson("/api/sections/{$this->section->id}/excuses")
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonPath('0.student.university_id', fn ($id) => $id !== null);
    }

    public function test_only_the_student_and_the_instructor_can_see_the_photo(): void
    {
        $excuse = $this->excuse();

        Sanctum::actingAs($this->student);
        $this->get("/api/excuses/{$excuse->id}/photo")->assertOk();

        Sanctum::actingAs($this->section->instructor);
        $this->get("/api/excuses/{$excuse->id}/photo")->assertOk();

        Sanctum::actingAs(User::factory()->create());
        $this->getJson("/api/excuses/{$excuse->id}/photo")->assertForbidden();
    }

    public function test_approving_marks_the_student_excused_in_that_days_lecture(): void
    {
        $lecture = LectureSession::factory()->open()->for($this->section)->create();
        $lecture->attendanceRecords()->create([
            'student_id' => $this->student->id, 'status' => 'absent', 'source' => 'auto', 'recorded_at' => now(),
        ]);
        $excuse = $this->excuse();

        Sanctum::actingAs($this->section->instructor);
        $this->postJson("/api/excuses/{$excuse->id}/review", ['status' => 'approved'])
            ->assertOk()
            ->assertJsonPath('status', 'approved');

        $this->assertDatabaseHas('attendance_records', [
            'lecture_session_id' => $lecture->id, 'student_id' => $this->student->id, 'status' => 'excused',
        ]);
    }

    public function test_approving_does_not_change_a_student_who_was_present(): void
    {
        $lecture = LectureSession::factory()->open()->for($this->section)->create();
        $lecture->attendanceRecords()->create([
            'student_id' => $this->student->id, 'status' => 'present', 'source' => 'scan', 'recorded_at' => now(),
        ]);

        Sanctum::actingAs($this->section->instructor);
        $this->postJson("/api/excuses/{$this->excuse()->id}/review", ['status' => 'approved'])->assertOk();

        $this->assertDatabaseHas('attendance_records', ['student_id' => $this->student->id, 'status' => 'present']);
    }

    public function test_lecture_ending_later_marks_an_excused_student_excused_not_absent(): void
    {
        $this->excuse('approved'); // e.g. an exam clash sent in advance
        $lecture = LectureSession::factory()->open()->for($this->section)->create();

        Sanctum::actingAs($this->section->instructor);
        $this->postJson("/api/sessions/{$lecture->id}/close")->assertOk();

        $this->assertDatabaseHas('attendance_records', [
            'lecture_session_id' => $lecture->id, 'student_id' => $this->student->id, 'status' => 'excused',
        ]);
    }

    public function test_rejected_excuse_changes_nothing_and_cannot_be_reviewed_twice(): void
    {
        $excuse = $this->excuse();

        Sanctum::actingAs($this->section->instructor);
        $this->postJson("/api/excuses/{$excuse->id}/review", ['status' => 'rejected'])->assertOk();
        $this->postJson("/api/excuses/{$excuse->id}/review", ['status' => 'approved'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'This excuse was already reviewed.');

        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_students_and_other_instructors_cannot_review(): void
    {
        $excuse = $this->excuse();

        Sanctum::actingAs($this->student);
        $this->postJson("/api/excuses/{$excuse->id}/review", ['status' => 'approved'])->assertForbidden();

        Sanctum::actingAs(User::factory()->instructor()->create());
        $this->postJson("/api/excuses/{$excuse->id}/review", ['status' => 'approved'])->assertForbidden();

        $this->assertSame('pending', $excuse->fresh()->status);
    }
}
