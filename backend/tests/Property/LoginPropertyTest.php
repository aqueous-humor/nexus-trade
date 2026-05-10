<?php

namespace Tests\Property;

use App\Models\User;
use Eris\Generator;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * P2 — Login property test.
 *
 * For any wrong password, the API SHALL return 422 (ValidationException) and
 * the user's failed_login_attempts counter SHALL be incremented by exactly 1.
 *
 * Feature: forex-broker-platform
 */
class LoginPropertyTest extends TestCase
{
    use RefreshDatabase;
    use TestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();  // prevent actual email sending (e.g. account locked notification)
        Event::fake(); // prevent broadcast events
    }

    /**
     * P2: Wrong password always returns 422 and increments failed_login_attempts by 1.
     *
     * The login controller throws ValidationException (HTTP 422) for wrong credentials.
     * For any registered user and any password that does not match their stored
     * password, the endpoint SHALL return 422 and failed_login_attempts SHALL
     * increase by exactly 1.
     */
    public function test_p2_wrong_password_returns_422_and_increments_failed_attempts(): void
    {
        $wrongPasswords = [
            'WrongPassword1!',
            'incorrect',
            'password123',
            'almost-correct!',
            'UPPERCASE_WRONG',
            '12345678',
        ];

        $this->forAll(
            Generator\elements(...$wrongPasswords)
        )
        ->withMaxSize(20) // limit iterations to keep test fast
        ->then(function (string $wrongPassword) {
            $correctPassword = 'CorrectPassword99!';

            // Create a fresh user each iteration (factory generates unique emails)
            $user = User::factory()->create([
                'password'               => Hash::make($correctPassword),
                'failed_login_attempts'  => 0,
                'locked_until'           => null,
            ]);

            $attemptsBefore = $user->fresh()->failed_login_attempts;

            $response = $this->postJson('/api/v1/auth/login', [
                'email'    => $user->email,
                'password' => $wrongPassword,
            ]);

            // Laravel throws ValidationException for wrong credentials → 422
            $response->assertStatus(422);

            $attemptsAfter = $user->fresh()->failed_login_attempts;

            $this->assertEquals(
                $attemptsBefore + 1,
                $attemptsAfter,
                "failed_login_attempts should increase by exactly 1 after wrong password"
            );
        });
    }

    /**
     * P2b: Multiple wrong passwords accumulate failed_login_attempts correctly.
     *
     * For N consecutive wrong password attempts (1–3, well below lockout threshold),
     * failed_login_attempts SHALL equal exactly N.
     */
    public function test_p2_multiple_wrong_passwords_accumulate_attempts(): void
    {
        $this->forAll(
            Generator\choose(1, 3) // 1–3 attempts (well below lockout threshold of 5)
        )
        ->withMaxSize(10)
        ->then(function (int $attempts) {
            $user = User::factory()->create([
                'password'              => Hash::make('CorrectPassword99!'),
                'failed_login_attempts' => 0,
                'locked_until'          => null,
            ]);

            for ($i = 0; $i < $attempts; $i++) {
                $this->postJson('/api/v1/auth/login', [
                    'email'    => $user->email,
                    'password' => 'wrong-password-' . $i,
                ]);
            }

            $this->assertEquals(
                $attempts,
                $user->fresh()->failed_login_attempts,
                "After {$attempts} wrong attempts, failed_login_attempts should be {$attempts}"
            );
        });
    }

    /**
     * P2c: Correct password does not increment failed_login_attempts.
     *
     * For any registered user with a known password, a successful login SHALL
     * NOT increase failed_login_attempts (the controller resets it to 0 on success).
     */
    public function test_p2_correct_password_does_not_increment_attempts(): void
    {
        $this->forAll(
            Generator\choose(0, 3) // pre-existing failed attempts
        )
        ->withMaxSize(10)
        ->then(function (int $existingAttempts) {
            $correctPassword = 'CorrectPassword99!';

            $user = User::factory()->create([
                'password'              => Hash::make($correctPassword),
                'failed_login_attempts' => $existingAttempts,
                'locked_until'          => null,
            ]);

            $response = $this->postJson('/api/v1/auth/login', [
                'email'    => $user->email,
                'password' => $correctPassword,
            ]);

            $response->assertStatus(200);

            // On success the controller resets attempts to 0 — it must not increase
            $this->assertLessThanOrEqual(
                $existingAttempts,
                $user->fresh()->failed_login_attempts,
                "Successful login should not increase failed_login_attempts"
            );
        });
    }
}
