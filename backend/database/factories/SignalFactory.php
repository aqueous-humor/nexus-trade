<?php

namespace Database\Factories;

use App\Models\Signal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Signal> */
class SignalFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'              => fake()->unique()->words(3, true),
            'description'       => fake()->sentence(),
            'provider_metadata' => ['provider' => fake()->company()],
            'status'            => 'active',
            'created_by'        => User::factory(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => 'inactive']);
    }
}
