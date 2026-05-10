<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Broker;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Account> */
class AccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'           => User::factory(),
            'broker_id'         => null,
            'type'              => 'demo',
            'broker_account_id' => null,
            'balance_cents'     => 1000000, // $10,000
            'leverage'          => 100,
            'status'            => 'active',
        ];
    }

    public function live(): static
    {
        return $this->state(fn () => [
            'type'              => 'live',
            'broker_id'         => Broker::factory(),
            'broker_account_id' => 'MT5-' . fake()->numerify('######'),
            'balance_cents'     => 0,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['status' => 'suspended']);
    }

    public function deactivated(): static
    {
        return $this->state(fn () => ['status' => 'deactivated']);
    }
}
