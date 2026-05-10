<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'first_name'            => fake()->firstName(),
            'last_name'             => fake()->lastName(),
            'email'                 => fake()->unique()->safeEmail(),
            'phone_number'          => fake()->optional()->phoneNumber(),
            'password'              => static::$password ??= Hash::make('password'),
            'role'                  => 'user',
            'email_verified_at'     => now(),
            'failed_login_attempts' => 0,
            'locked_until'          => null,
            'remember_token'        => Str::random(10),
        ];
    }

    /** User with unverified email. */
    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }

    /** Admin user. */
    public function admin(): static
    {
        return $this->state(fn () => ['role' => 'admin']);
    }

    /** Locked user account. */
    public function locked(): static
    {
        return $this->state(fn () => [
            'failed_login_attempts' => 5,
            'locked_until'          => now()->addMinutes(15),
        ]);
    }
}
