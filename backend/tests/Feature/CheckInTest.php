<?php

namespace Tests\Feature;

use App\Models\LectureSession;
use App\Models\User;
use App\Services\QrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CheckInTest extends TestCase
{
    use RefreshDatabase;

    // Same as the room factory. 0.0009° of latitude is about 100 m.
    private const ROOM_LAT = 31.9539;

    private const ROOM_LNG = 35.9106;

    private LectureSession $session;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-09 10:00:00'));
        $this->session = LectureSession::factory()->open()->create();
        $this->student = User::factory()->create();
        $this->session->section->students()->attach($this->student);
        $this->student->devices()->create(['device_uid' => 'my-phone', 'name' => 'iPhone']);
        Sanctum::actingAs($this->student);
    }

    public function test_student_in_the_room_is_marked_present(): void
    {
        $this->checkIn()
            ->assertCreated()
            ->assertJsonPath('status', 'present')
            ->assertJsonPath('source', 'scan')
            ->assertJsonPath('flags', []);

        $this->assertDatabaseHas('attendance_records', [
            'lecture_session_id' => $this->session->id,
            'student_id' => $this->student->id,
            'status' => 'present',
        ]);
    }

    public function test_code_from_the_previous_step_is_accepted_and_flagged(): void
    {
        $code = $this->currentCode();
        $this->travel(10)->seconds();

        $this->checkIn(['code' => $code])
            ->assertCreated()
            ->assertJsonPath('flags', ['token_near_expiry']);
    }

    public function test_screenshot_forwarded_20_seconds_later_is_rejected(): void
    {
        $code = $this->currentCode();
        $this->travel(20)->seconds();

        $this->checkIn(['code' => $code])
            ->assertUnprocessable()
            ->assertJsonPath('errors.code.0', 'This QR code has expired. Scan the code on the screen again.');
    }

    public function test_forged_code_is_rejected(): void
    {
        $forged = substr($this->currentCode(), 0, -4).'AAAA';

        $this->checkIn(['code' => $forged])->assertUnprocessable()->assertJsonValidationErrors('code');
        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_other_qr_codes_are_rejected(): void
    {
        $this->checkIn(['code' => 'https://example.com'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.code.0', "This isn't a UniCheck attendance code.");
    }

    public function test_student_not_in_the_section_is_rejected(): void
    {
        $outsider = User::factory()->create();
        $outsider->devices()->create(['device_uid' => 'outsider-phone']);
        Sanctum::actingAs($outsider);

        $this->checkIn(['device_uid' => 'outsider-phone'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.code.0', "You aren't enrolled in this class.");
    }

    public function test_check_in_after_the_window_closes_is_rejected(): void
    {
        $this->travel(16)->minutes();

        $this->checkIn()
            ->assertUnprocessable()
            ->assertJsonPath('errors.code.0', 'Check-in for this lecture is closed.');
    }

    public function test_mocked_location_is_rejected(): void
    {
        $this->checkIn(['is_mocked' => true])->assertUnprocessable()->assertJsonValidationErrors('location');
    }

    public function test_student_away_from_the_room_is_rejected(): void
    {
        $this->checkIn(['latitude' => self::ROOM_LAT + 0.01]) // about 1.1 km away
            ->assertUnprocessable()
            ->assertJsonPath('errors.location.0', 'You need to be in the lecture room to check in.');
    }

    public function test_very_inaccurate_gps_cannot_cover_a_long_distance(): void
    {
        $this->checkIn(['latitude' => self::ROOM_LAT + 0.01, 'accuracy_meters' => 5000])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('location');
    }

    public function test_weak_gps_near_the_building_is_accepted_and_flagged(): void
    {
        // 100 m away with 60 m accuracy: within 75 m radius + 50 m allowed error.
        $this->checkIn(['latitude' => self::ROOM_LAT + 0.0009, 'accuracy_meters' => 60])
            ->assertCreated()
            ->assertJsonPath('flags', ['weak_location_signal']);
    }

    public function test_student_far_from_the_rest_of_the_class_is_flagged(): void
    {
        foreach (range(1, 5) as $i) {
            $classmate = User::factory()->create();
            $this->session->section->students()->attach($classmate);
            $this->session->attendanceRecords()->create([
                'student_id' => $classmate->id,
                'status' => 'present',
                'source' => 'scan',
                'recorded_at' => now(),
                'latitude' => self::ROOM_LAT,
                'longitude' => self::ROOM_LNG,
                'accuracy_meters' => 5,
            ]);
        }

        // About 61 m from everyone else: inside the building area, but not with the class.
        $this->checkIn(['latitude' => self::ROOM_LAT + 0.00055])
            ->assertCreated()
            ->assertJsonPath('flags', ['far_from_class']);
    }

    public function test_scanning_twice_returns_the_same_record(): void
    {
        $id = $this->checkIn()->assertCreated()->json('id');

        $this->checkIn()->assertOk()->assertJsonPath('id', $id);
        $this->assertDatabaseCount('attendance_records', 1);
    }

    public function test_scan_does_not_override_the_instructors_decision(): void
    {
        $this->session->attendanceRecords()->create([
            'student_id' => $this->student->id,
            'status' => 'absent',
            'source' => 'manual',
            'recorded_at' => now(),
        ]);

        $this->checkIn()
            ->assertUnprocessable()
            ->assertJsonPath('errors.code.0', 'Your instructor has already recorded your attendance for this lecture.');
    }

    public function test_instructors_cannot_check_in(): void
    {
        Sanctum::actingAs($this->session->section->instructor);

        $this->checkIn()->assertForbidden();
    }

    public function test_check_in_from_someone_elses_phone_is_rejected(): void
    {
        $friend = User::factory()->create();
        $friend->devices()->create(['device_uid' => 'friends-phone']);

        $this->checkIn(['device_uid' => 'friends-phone'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.code.0', "This phone isn't registered to your account. Log out and log in again.");
    }

    public function test_check_in_records_the_phone_and_flags_a_new_second_phone(): void
    {
        // The student's old phone was added long ago; the new one today.
        $this->student->devices()->where('device_uid', 'my-phone')->update(['created_at' => now()->subMonth()]);
        $newPhone = $this->student->devices()->create(['device_uid' => 'new-phone']);

        $this->checkIn(['device_uid' => 'new-phone'])
            ->assertCreated()
            ->assertJsonPath('device_id', $newPhone->id)
            ->assertJsonPath('flags', ['new_phone']);
    }

    private function currentCode(): string
    {
        $qrCodes = app(QrCodeService::class);

        return $qrCodes->codeFor($this->session, $qrCodes->currentStep($this->session));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function checkIn(array $overrides = []): TestResponse
    {
        return $this->postJson('/api/check-ins', array_merge([
            'code' => $this->currentCode(),
            'latitude' => self::ROOM_LAT,
            'longitude' => self::ROOM_LNG,
            'accuracy_meters' => 10,
            'device_uid' => 'my-phone',
        ], $overrides));
    }
}
