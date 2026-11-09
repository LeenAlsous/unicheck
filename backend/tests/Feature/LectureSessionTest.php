<?php

namespace Tests\Feature;

use App\Enums\AttendanceSource;
use App\Enums\AttendanceStatus;
use App\Models\LectureSession;
use App\Models\Room;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LectureSessionTest extends TestCase
{
    use RefreshDatabase;

    private Section $section;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-09 10:00:00'));
        $this->section = Section::factory()->create();
        Sanctum::actingAs($this->section->instructor);
    }

    public function test_instructor_starts_a_session_in_the_usual_room(): void
    {
        $response = $this->postJson("/api/sections/{$this->section->id}/sessions")
            ->assertCreated()
            ->assertJsonPath('status', 'open')
            ->assertJsonPath('check_in_open', true)
            ->assertJsonPath('room.id', $this->section->default_room_id)
            ->assertJsonPath('qr_step_seconds', 10)
            ->assertJsonPath('server_time_ms', now()->getTimestampMs());

        $this->assertNotEmpty($response->json('qr_secret'));
        $this->assertTrue(Carbon::parse($response->json('closes_at'))->equalTo(now()->addMinutes(15)));
    }

    public function test_room_and_check_in_length_can_be_chosen(): void
    {
        $room = Room::factory()->create();

        $response = $this->postJson("/api/sections/{$this->section->id}/sessions", [
            'room_id' => $room->id,
            'duration_minutes' => 5,
        ])->assertCreated()->assertJsonPath('room.id', $room->id);

        $this->assertTrue(Carbon::parse($response->json('closes_at'))->equalTo(now()->addMinutes(5)));
    }

    public function test_section_without_a_usual_room_needs_one(): void
    {
        $this->section->update(['default_room_id' => null]);

        $this->postJson("/api/sections/{$this->section->id}/sessions")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('room_id');
    }

    public function test_only_one_session_can_be_open_at_a_time(): void
    {
        LectureSession::factory()->open()->for($this->section)->create();

        $this->postJson("/api/sections/{$this->section->id}/sessions")->assertConflict();
    }

    public function test_other_instructors_and_students_cannot_start_a_session(): void
    {
        Sanctum::actingAs(User::factory()->instructor()->create());
        $this->postJson("/api/sections/{$this->section->id}/sessions")->assertForbidden();

        Sanctum::actingAs(User::factory()->create());
        $this->postJson("/api/sections/{$this->section->id}/sessions")->assertForbidden();
    }

    public function test_instructor_can_get_the_qr_secret_again(): void
    {
        $session = LectureSession::factory()->open()->for($this->section)->create();

        $this->getJson("/api/sessions/{$session->id}")
            ->assertOk()
            ->assertJsonPath('qr_secret', $session->qr_secret);
    }

    public function test_students_never_see_the_qr_secret(): void
    {
        $session = LectureSession::factory()->open()->for($this->section)->create();
        $student = User::factory()->create();
        $this->section->students()->attach($student);
        Sanctum::actingAs($student);

        $this->getJson("/api/sessions/{$session->id}")->assertForbidden();
        $this->getJson('/api/sections')
            ->assertOk()
            ->assertJsonPath('0.open_session.id', $session->id)
            ->assertJsonMissingPath('0.open_session.qr_secret');
    }

    public function test_closing_marks_students_who_did_not_check_in_absent(): void
    {
        $session = LectureSession::factory()->open()->for($this->section)->create();
        [$present, $missing1, $missing2] = User::factory()->count(3)->create()->all();
        $this->section->students()->attach([$present->id, $missing1->id, $missing2->id]);
        $session->attendanceRecords()->create([
            'student_id' => $present->id,
            'status' => AttendanceStatus::Present,
            'source' => AttendanceSource::Scan,
            'recorded_at' => now(),
        ]);

        $this->postJson("/api/sessions/{$session->id}/close")
            ->assertOk()
            ->assertJsonPath('status', 'closed')
            ->assertJsonPath('check_in_open', false)
            ->assertJsonMissingPath('qr_secret');

        $this->assertDatabaseHas('attendance_records', ['student_id' => $present->id, 'status' => 'present']);
        $this->assertDatabaseHas('attendance_records', ['student_id' => $missing1->id, 'status' => 'absent', 'source' => 'auto']);
        $this->assertDatabaseHas('attendance_records', ['student_id' => $missing2->id, 'status' => 'absent', 'source' => 'auto']);

        // Closing again changes nothing.
        $this->postJson("/api/sessions/{$session->id}/close")->assertOk();
        $this->assertDatabaseCount('attendance_records', 3);
    }

    public function test_session_list_shows_attendance_counts(): void
    {
        $session = LectureSession::factory()->open()->for($this->section)->create();
        $student = User::factory()->create();
        $this->section->students()->attach($student);
        $session->close();

        $this->getJson("/api/sections/{$this->section->id}/sessions")
            ->assertOk()
            ->assertJsonPath('0.id', $session->id)
            ->assertJsonPath('0.present_count', 0)
            ->assertJsonPath('0.absent_count', 1);
    }
}
