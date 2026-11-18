<?php

namespace Tests\Feature;

use App\Models\LectureSession;
use App\Models\Section;
use App\Models\User;
use App\Services\QrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SectionLocationTest extends TestCase
{
    use RefreshDatabase;

    // About 11 km from the room in the factory (31.9539, 35.9106).
    private const SCIENCE_LAT = 32.0539;

    private const SCIENCE_LNG = 35.9106;

    private function setLocation(Section $section, array $data = [])
    {
        return $this->putJson("/api/sections/{$section->id}/location", array_merge([
            'building' => 'Science Faculty',
            'room_number' => '204',
            'latitude' => self::SCIENCE_LAT,
            'longitude' => self::SCIENCE_LNG,
            'accuracy_meters' => 10,
        ], $data));
    }

    public function test_instructor_sets_the_section_location(): void
    {
        $section = Section::factory()->create();
        $roomLatitude = $section->defaultRoom->latitude;
        Sanctum::actingAs($section->instructor);

        $this->setLocation($section)
            ->assertOk()
            ->assertJsonPath('building', 'Science Faculty')
            ->assertJsonPath('room_number', '204')
            ->assertJsonPath('latitude', self::SCIENCE_LAT);

        // The shared room is not changed.
        $this->assertSame($roomLatitude, $section->defaultRoom->fresh()->latitude);
    }

    public function test_names_can_change_without_a_new_gps_reading(): void
    {
        $section = Section::factory()->create();
        Sanctum::actingAs($section->instructor);

        $this->setLocation($section, ['accuracy_meters' => null])->assertOk();
    }

    public function test_imprecise_location_and_missing_fields_are_refused(): void
    {
        $section = Section::factory()->create();
        Sanctum::actingAs($section->instructor);

        $this->setLocation($section, ['accuracy_meters' => 500])
            ->assertJsonPath('errors.accuracy_meters.0', 'Your location is not precise enough. Go near a window and try again.');
        $this->setLocation($section, ['building' => ''])->assertJsonValidationErrors('building');
        $this->setLocation($section, ['room_number' => ''])->assertJsonValidationErrors('room_number');
    }

    public function test_only_the_sections_instructor_can_set_it(): void
    {
        $section = Section::factory()->create();

        Sanctum::actingAs(User::factory()->instructor()->create());
        $this->setLocation($section)->assertForbidden();

        Sanctum::actingAs(User::factory()->create());
        $this->setLocation($section)->assertForbidden();

        $this->assertNull($section->fresh()->latitude);
    }

    public function test_check_in_uses_the_section_location_instead_of_the_room(): void
    {
        $section = Section::factory()->create();
        $section->update(['building' => 'Science Faculty', 'room_number' => '204', 'latitude' => self::SCIENCE_LAT, 'longitude' => self::SCIENCE_LNG]);
        $lecture = LectureSession::factory()->open()->for($section)->create();
        [$atScience, $atOldRoom] = User::factory()->count(2)->create()->all();
        $section->students()->attach([$atScience->id, $atOldRoom->id]);
        $atScience->devices()->create(['device_uid' => 'phone-1']);
        $atOldRoom->devices()->create(['device_uid' => 'phone-2']);

        $qr = app(QrCodeService::class);
        $code = $qr->codeFor($lecture, $qr->currentStep($lecture));
        $checkIn = fn (User $student, string $phone, float $lat) => $this->actingAs($student)->postJson('/api/check-ins', [
            'code' => $code, 'latitude' => $lat, 'longitude' => self::SCIENCE_LNG, 'accuracy_meters' => 10, 'device_uid' => $phone,
        ]);

        $checkIn($atScience, 'phone-1', self::SCIENCE_LAT)->assertCreated();
        $checkIn($atOldRoom, 'phone-2', 31.9539)->assertUnprocessable()->assertJsonValidationErrors('location');
    }

    public function test_a_lecture_can_start_with_only_a_section_location(): void
    {
        $section = Section::factory()->create(['default_room_id' => null]);
        Sanctum::actingAs($section->instructor);

        $this->postJson("/api/sections/{$section->id}/sessions")
            ->assertUnprocessable()
            ->assertJsonPath('errors.room_id.0', 'Set where this section meets first: Options, then Section location.');

        $this->setLocation($section)->assertOk();
        $this->postJson("/api/sections/{$section->id}/sessions")->assertCreated()->assertJsonPath('room', null);
    }
}
