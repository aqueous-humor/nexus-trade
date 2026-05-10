<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Duration;
use App\Models\Investment;
use App\Models\InvestmentPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Investment> */
class InvestmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'       => User::factory(),
            'account_id'    => Account::factory(),
            'plan_id'       => InvestmentPlan::factory(),
            'duration_id'   => Duration::factory(),
            'amount_cents'  => fake()->numberBetween(10000, 500000),
            'profit_cents'  => 0,
            'status'        => 'pending',
            'result'        => null,
            'maturity_at'   => now()->addDays(7),
            'terms_version' => 'v1.0',
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => 'active', 'activated_at' => now()]);
    }

    public function completed(string $result = 'WIN'): static
    {
        return $this->state(fn () => [
            'status'       => 'completed',
            'result'       => $result,
            'profit_cents' => $result === 'WIN' ? fake()->numberBetween(1000, 50000) : 0,
            'completed_at' => now(),
        ]);
    }
}
