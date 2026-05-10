<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Duration;
use App\Models\Investment;
use App\Models\InvestmentPlan;
use App\Models\User;
use Illuminate\Database\Seeder;

class InvestmentSeeder extends Seeder
{
    public function run(): void
    {
        $plan     = InvestmentPlan::where('status', 'active')->first();
        $duration = Duration::first();

        if (! $plan || ! $duration) {
            $this->command->warn('No active plans or durations found. Skipping InvestmentSeeder.');
            return;
        }

        for ($i = 1; $i <= 5; $i++) {
            $user = User::where('email', "user{$i}@nexustrade.local")->first();

            if (! $user) {
                continue;
            }

            $account = Account::where('user_id', $user->id)->first();

            if (! $account) {
                continue;
            }

            // Skip if user already has investments seeded
            if (Investment::where('user_id', $user->id)->count() >= 2) {
                continue;
            }

            // Investment 1: active
            Investment::factory()
                ->active()
                ->create([
                    'user_id'       => $user->id,
                    'account_id'    => $account->id,
                    'plan_id'       => $plan->id,
                    'duration_id'   => $duration->id,
                    'amount_cents'  => random_int(10000, 100000),
                    'terms_version' => 'v1.0',
                ]);

            // Investment 2: completed WIN
            Investment::factory()
                ->completed('WIN')
                ->create([
                    'user_id'       => $user->id,
                    'account_id'    => $account->id,
                    'plan_id'       => $plan->id,
                    'duration_id'   => $duration->id,
                    'amount_cents'  => random_int(10000, 100000),
                    'terms_version' => 'v1.0',
                ]);
        }
    }
}
