<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Broker;
use App\Models\User;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $alphaBroker = Broker::where('name', 'AlphaFX')->first();

        for ($i = 1; $i <= 10; $i++) {
            $user = User::where('email', "user{$i}@nexustrade.local")->first();

            if (! $user) {
                continue;
            }

            // Demo account
            Account::firstOrCreate(
                [
                    'user_id'   => $user->id,
                    'type'      => 'demo',
                    'broker_id' => null,
                ],
                [
                    'balance_cents' => 1_000_000, // $10,000
                    'leverage'      => 100,
                    'status'        => 'active',
                ]
            );

            // Live account linked to AlphaFX
            if ($alphaBroker) {
                Account::firstOrCreate(
                    [
                        'user_id'   => $user->id,
                        'type'      => 'live',
                        'broker_id' => $alphaBroker->id,
                    ],
                    [
                        'balance_cents' => random_int(50000, 500000),
                        'leverage'      => $alphaBroker->default_leverage,
                        'status'        => 'active',
                    ]
                );
            }
        }
    }
}
