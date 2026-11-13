<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceTest extends TestCase
{
    use RefreshDatabase;

    private function login(string $universityId, ?string $deviceUid)
    {
        return $this->postJson('/api/auth/login', [
            'university_id' => $universityId,
            'password' => 'password',
            'device_uid' => $deviceUid,
            'device_name' => 'iPhone',
        ]);
    }

    public function test_logging_in_registers_the_phone(): void
    {
        $student = User::factory()->create(['university_id' => 'S0001']);

        $this->login('S0001', 'phone-a')->assertOk();

        $this->assertDatabaseHas('devices', ['student_id' => $student->id, 'device_uid' => 'phone-a', 'name' => 'iPhone']);
    }

    public function test_a_student_can_use_several_phones(): void
    {
        $student = User::factory()->create(['university_id' => 'S0001']);

        $this->login('S0001', 'old-phone')->assertOk();
        $this->login('S0001', 'new-phone')->assertOk();
        $this->login('S0001', 'old-phone')->assertOk(); // the old one still works

        $this->assertSame(2, $student->devices()->count());
    }

    public function test_a_phone_cannot_log_in_to_another_students_account(): void
    {
        User::factory()->create(['university_id' => 'S0001']);
        User::factory()->create(['university_id' => 'S0002']);
        $this->login('S0001', 'phone-a')->assertOk();

        $this->login('S0002', 'phone-a')
            ->assertUnprocessable()
            ->assertJsonPath('errors.university_id.0', 'This phone is registered to another student. Each phone can only be used by one student.');
    }

    public function test_students_must_log_in_from_the_phone_app(): void
    {
        User::factory()->create(['university_id' => 'S0001']);

        $this->login('S0001', null)
            ->assertUnprocessable()
            ->assertJsonPath('errors.university_id.0', 'Students must log in from the UniCheck phone app.');
    }

    public function test_instructors_do_not_need_a_phone(): void
    {
        User::factory()->instructor()->create(['university_id' => 'I1001']);

        $this->login('I1001', null)->assertOk();
        $this->assertDatabaseCount('devices', 0);
    }

    public function test_student_sees_their_phones_and_which_one_is_this_one(): void
    {
        User::factory()->create(['university_id' => 'S0001']);
        $this->login('S0001', 'old-phone');
        $token = $this->login('S0001', 'new-phone')->json('token');

        $phones = $this->withToken($token)->getJson('/api/devices')->assertOk()->assertJsonCount(2)->json();

        $thisPhone = collect($phones)->firstWhere('is_this_phone', true);
        $this->assertNotNull($thisPhone);
        $this->assertArrayNotHasKey('device_uid', $thisPhone); // never sent back
    }

    public function test_removing_a_lost_phone_logs_it_out_and_frees_it(): void
    {
        $student = User::factory()->create(['university_id' => 'S0001']);
        User::factory()->create(['university_id' => 'S0002']);
        $lostPhoneToken = $this->login('S0001', 'lost-phone')->json('token');
        $newPhoneToken = $this->login('S0001', 'new-phone')->json('token');
        $lostPhone = $student->devices()->where('device_uid', 'lost-phone')->first();

        $this->withToken($newPhoneToken)->deleteJson("/api/devices/{$lostPhone->id}")->assertNoContent();

        // The lost phone is logged out...
        $this->app['auth']->forgetGuards();
        $this->withToken($lostPhoneToken)->getJson('/api/auth/me')->assertUnauthorized();
        // ...and whoever has it now can use it for their own account.
        $this->login('S0002', 'lost-phone')->assertOk();
    }

    public function test_cannot_remove_another_students_phone(): void
    {
        $other = User::factory()->create(['university_id' => 'S0002']);
        $otherPhone = $other->devices()->create(['device_uid' => 'their-phone']);
        User::factory()->create(['university_id' => 'S0001']);
        $token = $this->login('S0001', 'my-phone')->json('token');

        $this->withToken($token)->deleteJson("/api/devices/{$otherPhone->id}")->assertNotFound();
        $this->assertDatabaseHas('devices', ['id' => $otherPhone->id]);
    }
}
