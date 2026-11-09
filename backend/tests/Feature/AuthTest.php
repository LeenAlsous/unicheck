<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_log_in_with_university_id_and_password(): void
    {
        $user = User::factory()->instructor()->create(['university_id' => 'I1001']);

        $response = $this->postJson('/api/auth/login', [
            'university_id' => 'I1001',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.role', 'instructor')
            ->assertJsonMissingPath('user.password');
        $this->assertNotEmpty($response->json('token'));
    }

    public function test_wrong_password_is_rejected(): void
    {
        User::factory()->create(['university_id' => 'S0001']);

        $this->postJson('/api/auth/login', ['university_id' => 'S0001', 'password' => 'wrong'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('university_id');
    }

    public function test_unknown_user_gets_the_same_error_as_a_wrong_password(): void
    {
        $this->postJson('/api/auth/login', ['university_id' => 'S9999', 'password' => 'password'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.university_id.0', 'Invalid university ID or password.');
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        User::factory()->inactive()->create(['university_id' => 'S0002']);

        $this->postJson('/api/auth/login', ['university_id' => 'S0002', 'password' => 'password'])
            ->assertUnprocessable();
    }

    public function test_me_requires_a_token(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_me_returns_the_logged_in_user(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('university_id', $user->university_id)
            ->assertJsonPath('role', 'student');
    }

    public function test_logout_revokes_the_token(): void
    {
        User::factory()->create(['university_id' => 'S0003']);
        $token = $this->postJson('/api/auth/login', ['university_id' => 'S0003', 'password' => 'password'])
            ->json('token');

        $this->withToken($token)->postJson('/api/auth/logout')->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_role_middleware_only_allows_listed_roles(): void
    {
        Route::middleware(['api', 'auth:sanctum', 'role:instructor,admin'])
            ->get('/api/test-instructors-only', fn () => ['ok' => true]);

        Sanctum::actingAs(User::factory()->create());
        $this->getJson('/api/test-instructors-only')->assertForbidden();

        Sanctum::actingAs(User::factory()->instructor()->create());
        $this->getJson('/api/test-instructors-only')->assertOk();
    }
}
