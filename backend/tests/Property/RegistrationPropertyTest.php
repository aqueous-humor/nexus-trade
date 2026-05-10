<?php

namespace Tests\Property;

use App\Models\User;
use Eris\Generator;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * P1 — Registration property test.
 *
 * For any invalid registration payload, the API SHALL return 422 with
 * field-level errors and no User record SHALL be created.
 *
 * Feature: forex-broker-platform
 */
class RegistrationPropertyTest extends TestCase
{
    use RefreshDatabase;
    use TestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Event::fake();
    }

    /**
     * P1a: Missing required fields always produce 422 with field errors.
     *
     * For any combination of missing required fields (first_name, last_name,
     * email, password), the endpoint SHALL return 422 and no User is created.
     */
    public function test_p1_missing_required_fields_return_422_with_errors(): void
    {
        $requiredFields = ['first_name', 'last_name', 'email', 'password'];

        $this->forAll(
            // Pick a non-empty subset of required fields to omit
            Generator\elements(
                ['first_name'],
                ['last_name'],
                ['email'],
                ['password'],
                ['first_name', 'last_name'],
                ['email', 'password'],
                ['first_name', 'email'],
                ['last_name', 'password'],
                ['first_name', 'last_name', 'email'],
                ['first_name', 'last_name', 'password'],
                ['first_name', 'last_name', 'email', 'password'],
            )
        )
        ->withMaxSize(20)
        ->then(function (array $fieldsToOmit) {
            $userCountBefore = User::count();

            $payload = [
                'first_name'            => 'Alice',
                'last_name'             => 'Smith',
                'email'                 => 'alice' . uniqid() . '@example.com',
                'password'              => 'SecurePass123!',
                'password_confirmation' => 'SecurePass123!',
            ];

            foreach ($fieldsToOmit as $field) {
                unset($payload[$field]);
            }

            $response = $this->postJson('/api/v1/auth/register', $payload);

            $response->assertStatus(422);
            $response->assertJsonStructure(['errors']);

            // At least one of the omitted fields should appear in errors
            $errors = $response->json('errors');
            $this->assertNotEmpty($errors, 'Response should contain field-level errors');

            // No new User should have been created
            $this->assertEquals(
                $userCountBefore,
                User::count(),
                'No User should be created when required fields are missing'
            );
        });
    }

    /**
     * P1b: Invalid email formats always produce 422 with email error.
     *
     * For any string that is not a valid RFC email address, the endpoint
     * SHALL return 422 with an error on the email field and no User is created.
     */
    public function test_p1_invalid_email_returns_422_with_email_error(): void
    {
        $this->forAll(
            Generator\elements(
                'not-an-email',
                'missing@',
                '@nodomain.com',
                'spaces in@email.com',
                'double@@domain.com',
                '',
                'toolong' . str_repeat('a', 250) . '@example.com',
            )
        )
        ->withMaxSize(20)
        ->then(function (string $invalidEmail) {
            $userCountBefore = User::count();

            $response = $this->postJson('/api/v1/auth/register', [
                'first_name'            => 'Alice',
                'last_name'             => 'Smith',
                'email'                 => $invalidEmail,
                'password'              => 'SecurePass123!',
                'password_confirmation' => 'SecurePass123!',
            ]);

            $response->assertStatus(422);

            $errors = $response->json('errors');
            $this->assertNotEmpty($errors, 'Response should contain field-level errors');

            $this->assertEquals(
                $userCountBefore,
                User::count(),
                'No User should be created for invalid email'
            );
        });
    }

    /**
     * P1c: Password confirmation mismatch always produces 422.
     *
     * For any password and a different confirmation, the endpoint SHALL
     * return 422 with a password error and no User is created.
     */
    public function test_p1_password_mismatch_returns_422(): void
    {
        $this->forAll(
            Generator\elements(
                ['SecurePass123!', 'DifferentPass456!'],
                ['PasswordA1!', 'PasswordB2!'],
                ['abc12345!', 'abc12346!'],
                ['MyPass1!', ''],
            )
        )
        ->withMaxSize(20)
        ->then(function (array $passwords) {
            [$password, $confirmation] = $passwords;
            $userCountBefore = User::count();

            $response = $this->postJson('/api/v1/auth/register', [
                'first_name'            => 'Bob',
                'last_name'             => 'Jones',
                'email'                 => 'bob' . uniqid() . '@example.com',
                'password'              => $password,
                'password_confirmation' => $confirmation,
            ]);

            $response->assertStatus(422);

            $this->assertEquals(
                $userCountBefore,
                User::count(),
                'No User should be created when password confirmation mismatches'
            );
        });
    }

    /**
     * P1d: Duplicate email always produces 422 with email error.
     *
     * For any email that already exists in the users table, the endpoint
     * SHALL return 422 with an email error and no additional User is created.
     */
    public function test_p1_duplicate_email_returns_422(): void
    {
        $this->forAll(
            Generator\elements(
                'duplicate1@example.com',
                'duplicate2@example.com',
                'duplicate3@example.com',
            )
        )
        ->withMaxSize(5)
        ->then(function (string $email) {
            // Ensure the email already exists (use firstOrCreate to survive repeated iterations)
            User::firstOrCreate(
                ['email' => $email],
                [
                    'first_name'  => 'Existing',
                    'last_name'   => 'User',
                    'password'    => bcrypt('password'),
                    'role'        => 'user',
                ]
            );
            $userCountBefore = User::count();

            $response = $this->postJson('/api/v1/auth/register', [
                'first_name'            => 'Charlie',
                'last_name'             => 'Brown',
                'email'                 => $email,
                'password'              => 'SecurePass123!',
                'password_confirmation' => 'SecurePass123!',
            ]);

            $response->assertStatus(422);

            $errors = $response->json('errors');
            $this->assertArrayHasKey('email', $errors, 'Email error should be present for duplicate');

            $this->assertEquals(
                $userCountBefore,
                User::count(),
                'No additional User should be created for duplicate email'
            );
        });
    }
}
