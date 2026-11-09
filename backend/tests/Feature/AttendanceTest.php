<?php

namespace Tests\Feature;

use App\Enums\AttendanceSource;
use App\Enums\AttendanceStatus;
use App\Models\LectureSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    private LectureSession $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->session = LectureSession::factory()->open()->create();
        Sanctum::actingAs($this->session->section->instructor);
    }

    public function test_live_list_shows_who_checked_in_and_who_has_not(): void
    {
        $here = User::factory()->create(['university_id' => 'S0001']);
        $notYet = User::factory()->create(['university_id' => 'S0002']);
        $this->session->section->students()->attach([$here->id, $notYet->id]);
        $this->session->attendanceRecords()->create([
            'student_id' => $here->id,
            'status' => AttendanceStatus::Present,
            'source' => AttendanceSource::Scan,
            'recorded_at' => now(),
        ]);

        $this->getJson("/api/sessions/{$this->session->id}/attendance")
            ->assertOk()
            ->assertJsonPath('session.id', $this->session->id)
            ->assertJsonPath('summary', ['enrolled' => 2, 'present' => 1, 'absent' => 0, 'excused' => 0, 'not_checked_in' => 1])
            ->assertJsonPath('students.0.student.university_id', 'S0001')
            ->assertJsonPath('students.0.attendance.status', 'present')
            ->assertJsonPath('students.1.student.university_id', 'S0002')
            ->assertJsonPath('students.1.attendance', null);
    }

    public function test_instructor_can_excuse_a_student_with_a_note(): void
    {
        $student = User::factory()->create();
        $this->session->section->students()->attach($student);

        $this->putJson("/api/sessions/{$this->session->id}/attendance/{$student->id}", [
            'status' => 'excused',
            'note' => 'Medical report',
        ])
            ->assertSuccessful()
            ->assertJsonPath('status', 'excused')
            ->assertJsonPath('source', 'manual')
            ->assertJsonPath('note', 'Medical report');
    }

    public function test_cannot_mark_a_student_from_another_section(): void
    {
        $outsider = User::factory()->create();

        $this->putJson("/api/sessions/{$this->session->id}/attendance/{$outsider->id}", ['status' => 'present'])
            ->assertNotFound();
    }

    public function test_status_must_be_valid(): void
    {
        $student = User::factory()->create();
        $this->session->section->students()->attach($student);

        $this->putJson("/api/sessions/{$this->session->id}/attendance/{$student->id}", ['status' => 'late'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_other_instructors_cannot_see_or_change_attendance(): void
    {
        $student = User::factory()->create();
        $this->session->section->students()->attach($student);
        Sanctum::actingAs(User::factory()->instructor()->create());

        $this->getJson("/api/sessions/{$this->session->id}/attendance")->assertForbidden();
        $this->putJson("/api/sessions/{$this->session->id}/attendance/{$student->id}", ['status' => 'present'])
            ->assertForbidden();
    }
}
