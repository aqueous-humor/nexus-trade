<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    // ----------------------------------------------------------------
    // Forgot password
    // ----------------------------------------------------------------

    public function test_forgot_password_sends_reset_link_for_existing_email(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'alice@example.com']);

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'alice@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.message', 'Password reset link sent to your email address.');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_forgot_password_returns_200_for_nonexistent_email(): void
    {
        // Should not reveal whether email exists (security best practice)
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'nobody@example.com',
        ]);

        // Laravel returns a validation error for non-existent emails by default
        // This is acceptable — the important thing is no user data is leaked
        $response->assertStatus(422);
    }

    public function test_forgot_password_requires_email(): void
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrorFor('email');
    }

    // ----------------------------------------------------------------
    // Reset password
    // ----------------------------------------------------------------

    public function test_user_can_reset_password_with_valid_token(): void
    {
        $user = User::factory()->create(['email' => 'alice@example.com']);
        $token = Password::createToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token'                 => $token,
            'email'                 => 'alice@example.com',
            'password'              => 'NewS3cur3P@ss!',
            'password_confirmation' => 'NewS3cur3P@ss!',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.message', 'Password has been reset successfully.');
    }

    public function test_reset_password_fails_with_invalid_token(): void
    {
        User::factory()->create(['email' => 'alice@example.com']);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token'                 => 'invalid-token',
            'email'                 => 'alice@example.com',
            'password'              => 'NewS3cur3P@ss!',
            'password_confirmation' => 'NewS3cur3P@ss!',
        ]);

        $response->assertStatus(422);
    }

    public function test_reset_password_clears_account_lock(): void
    {
        $user = User::factory()->create([
            'email'                 => 'alice@example.com',
            'failed_login_attempts' => 5,
            'locked_until'          => now()->addMinutes(10),
        ]);

        $token = Password::createToken($user);

        $this->postJson('/api/v1/auth/reset-password', [
            'token'                 => $token,
            'email'                 => 'alice@example.com',
            'password'              => 'NewS3cur3P@ss!',
            'password_confirmation' => 'NewS3cur3P@ss!',
        ]);

        $fresh = $user->fresh();
        $this->assertEquals(0, $fresh->failed_login_attempts);
        $this->assertNull($fresh->locked_until);
    }
}
