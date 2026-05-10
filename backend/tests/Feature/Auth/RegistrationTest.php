<?php

namespace Tests\Feature\Auth;

use App\Models\NotificationPreference;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    // ----------------------------------------------------------------
    // Happy path
    // ----------------------------------------------------------------

    public function test_user_can_register_with_valid_data(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'first_name'            => 'Alice',
            'last_name'             => 'Smith',
            'email'                 => 'alice@example.com',
            'phone_number'          => '+447700900000',
            'password'              => 'S3cur3P@ss!',
            'password_confirmation' => 'S3cur3P@ss!',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.user.email', 'alice@example.com')
            ->assertJsonPath('data.user.role', 'user');

        // User record created
        $this->assertDatabaseHas('users', ['email' => 'alice@example.com']);

        // Wallet created with zero balance
        $user = User::where('email', 'alice@example.com')->first();
        $this->assertNotNull($user->wallet);
        $this->assertEquals(0, $user->wallet->balance_cents);

        // Notification preferences created
        $this->assertDatabaseHas('notification_preferences', ['user_id' => $user->id]);
    }

    public function test_registration_creates_wallet_and_notification_preferences(): void
    {
        Mail::fake();

        $this->postJson('/api/v1/auth/register', [
            'first_name'            => 'Bob',
            'last_name'             => 'Jones',
            'email'                 => 'bob@example.com',
            'password'              => 'S3cur3P@ss!',
            'password_confirmation' => 'S3cur3P@ss!',
        ]);

        $user = User::where('email', 'bob@example.com')->first();

        $this->assertInstanceOf(Wallet::class, $user->wallet);
        $this->assertInstanceOf(NotificationPreference::class, $user->notificationPreference);
        $this->assertTrue($user->notificationPreference->investment_created);
        $this->assertTrue($user->notificationPreference->deposit_confirmed);
    }

    // ----------------------------------------------------------------
    // Error paths
    // ----------------------------------------------------------------

    public function test_registration_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'alice@example.com']);

        $response = $this->postJson('/api/v1/auth/register', [
            'first_name'            => 'Alice',
            'last_name'             => 'Smith',
            'email'                 => 'alice@example.com',
            'password'              => 'S3cur3P@ss!',
            'password_confirmation' => 'S3cur3P@ss!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrorFor('email');
    }

    public function test_registration_fails_with_missing_required_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrorFor('first_name')
            ->assertJsonValidationErrorFor('last_name')
            ->assertJsonValidationErrorFor('email')
            ->assertJsonValidationErrorFor('password');
    }

    public function test_registration_fails_when_passwords_do_not_match(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'first_name'            => 'Alice',
            'last_name'             => 'Smith',
            'email'                 => 'alice@example.com',
            'password'              => 'S3cur3P@ss!',
            'password_confirmation' => 'DifferentPass!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrorFor('password');
    }

    public function test_registration_fails_with_invalid_email_format(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'first_name'            => 'Alice',
            'last_name'             => 'Smith',
            'email'                 => 'not-an-email',
            'password'              => 'S3cur3P@ss!',
            'password_confirmation' => 'S3cur3P@ss!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrorFor('email');
    }

    public function test_registration_fails_with_short_password(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'first_name'            => 'Alice',
            'last_name'             => 'Smith',
            'email'                 => 'alice@example.com',
            'password'              => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrorFor('password');
    }
}
