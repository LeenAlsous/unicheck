<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_imported_account_logs_in_with_its_id_and_must_change_the_password(): void
    {
        User::newAccount('S0001', 'New Student', 'student');

        $this->postJson('/api/auth/login', ['university_id' => 'S0001', 'password' => 'S0001', 'device_uid' => 'phone-a'])
            ->assertOk()
            ->assertJsonPath('user.must_change_password', true);
    }

    public function test_user_changes_their_password(): void
    {
        $user = User::newAccount('S0001', 'New Student', 'student');
        Sanctum::actingAs($user);

        $this->postJson('/api/auth/password', [
            'current_password' => 'S0001',
            'new_password' => 'my-new-pass',
            'new_password_confirmation' => 'my-new-pass',
        ])
            ->assertOk()
            ->assertJsonPath('must_change_password', false);

        $this->assertTrue(Hash::check('my-new-pass', $user->fresh()->password));
    }

    public function test_wrong_current_password_is_refused(): void
    {
        Sanctum::actingAs(User::newAccount('S0001', 'New Student', 'student'));

        $this->postJson('/api/auth/password', [
            'current_password' => 'wrong',
            'new_password' => 'my-new-pass',
            'new_password_confirmation' => 'my-new-pass',
        ])->assertUnprocessable()->assertJsonPath('errors.current_password.0', 'Your current password is wrong.');
    }

    public function test_new_password_rules(): void
    {
        Sanctum::actingAs(User::newAccount('S0001234', 'New Student', 'student'));

        $change = fn (array $data) => $this->postJson('/api/auth/password', ['current_password' => 'S0001234'] + $data);

        $change(['new_password' => 'short', 'new_password_confirmation' => 'short'])->assertJsonValidationErrors('new_password');
        $change(['new_password' => 'long-enough', 'new_password_confirmation' => 'different'])->assertJsonValidationErrors('new_password');
        $change(['new_password' => 'S0001234', 'new_password_confirmation' => 'S0001234'])
            ->assertJsonPath('errors.new_password.0', "Your new password can't be your university ID.");
    }
}
