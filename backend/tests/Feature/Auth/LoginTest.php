<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    // ----------------------------------------------------------------
    // Happy path
    // ----------------------------------------------------------------

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email'    => 'alice@example.com',
            'password' => bcrypt('S3cur3P@ss!'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'alice@example.com',
            'password' => 'S3cur3P@ss!',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.user.email', 'alice@example.com')
            ->assertJsonStructure(['data' => ['user', 'token']]);
    }

    public function test_login_resets_failed_attempts_on_success(): void
    {
        $user = User::factory()->create([
            'email'                  => 'alice@example.com',
            'password'               => bcrypt('S3cur3P@ss!'),
            'failed_login_attempts'  => 3,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email'    => 'alice@example.com',
            'password' => 'S3cur3P@ss!',
        ]);

        $this->assertEquals(0, $user->fresh()->failed_login_attempts);
    }

    // ----------------------------------------------------------------
    // Error paths
    // ----------------------------------------------------------------

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email'    => 'alice@example.com',
            'password' => bcrypt('S3cur3P@ss!'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'alice@example.com',
            'password' => 'WrongPassword!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrorFor('email');
    }

    public function test_login_increments_failed_attempts_on_wrong_password(): void
    {
        $user = User::factory()->create([
            'email'                 => 'alice@example.com',
            'password'              => bcrypt('S3cur3P@ss!'),
            'failed_login_attempts' => 0,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email'    => 'alice@example.com',
            'password' => 'WrongPassword!',
        ]);

        $this->assertEquals(1, $user->fresh()->failed_login_attempts);
    }

    public function test_login_fails_with_nonexistent_email(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'nobody@example.com',
            'password' => 'S3cur3P@ss!',
        ]);

        $response->assertStatus(422);
    }

    public function test_account_is_locked_after_five_failed_attempts(): void
    {
        $user = User::factory()->create([
            'email'    => 'alice@example.com',
            'password' => bcrypt('S3cur3P@ss!'),
        ]);

        // Make 5 failed attempts
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email'    => 'alice@example.com',
                'password' => 'WrongPassword!',
            ]);
        }

        $this->assertNotNull($user->fresh()->locked_until);
        $this->assertTrue($user->fresh()->isLocked());
    }

    public function test_locked_account_cannot_login_with_correct_password(): void
    {
        User::factory()->create([
            'email'        => 'alice@example.com',
            'password'     => bcrypt('S3cur3P@ss!'),
            'locked_until' => Carbon::now()->addMinutes(10),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'alice@example.com',
            'password' => 'S3cur3P@ss!',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('code', 'ACCOUNT_LOCKED');
    }

    // ----------------------------------------------------------------
    // Logout
    // ----------------------------------------------------------------

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200);
    }

    // ----------------------------------------------------------------
    // Me endpoint
    // ----------------------------------------------------------------

    public function test_me_returns_authenticated_user_profile(): void
    {
        $user = User::factory()->create(['email' => 'alice@example.com']);

        $response = $this->actingAs($user)->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.email', 'alice@example.com');
    }

    public function test_me_returns_401_when_unauthenticated(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }
}
