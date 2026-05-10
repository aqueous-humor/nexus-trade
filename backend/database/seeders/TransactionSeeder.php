<?php

namespace Database\Seeders;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('role', 'user')->get();

        foreach ($users as $user) {
            $wallet = Wallet::where('user_id', $user->id)->first();

            if (! $wallet) {
                continue;
            }

            // Skip if user already has transactions seeded
            if (Transaction::where('user_id', $user->id)->count() >= 3) {
                continue;
            }

            // 2 deposit transactions
            for ($d = 1; $d <= 2; $d++) {
                $amount = random_int(10000, 100000); // $100–$1,000
                $fee    = (int) round($amount * 0.001); // 0.1% fee

                Transaction::create([
                    'user_id'         => $user->id,
                    'wallet_id'       => $wallet->id,
                    'type'            => 'deposit',
                    'status'          => 'completed',
                    'amount_cents'    => $amount,
                    'fee_cents'       => $fee,
                    'net_amount_cents' => $amount - $fee,
                    'currency'        => 'USD',
                    'exchange_rate'   => 1.0,
                    'provider'        => 'binance',
                    'reference'       => 'DEP-' . strtoupper(Str::random(8)),
                    'metadata'        => ['seeded' => true],
                ]);
            }

            // 1 withdrawal transaction
            $amount = random_int(5000, 50000); // $50–$500
            $fee    = 200; // $2.00 fixed fee

            Transaction::create([
                'user_id'          => $user->id,
                'wallet_id'        => $wallet->id,
                'type'             => 'withdrawal',
                'status'           => 'completed',
                'amount_cents'     => $amount,
                'fee_cents'        => $fee,
                'net_amount_cents' => $amount - $fee,
                'currency'         => 'USD',
                'exchange_rate'    => 1.0,
                'provider'         => 'kucoin',
                'destination_address' => '0x' . strtolower(Str::random(40)),
                'reference'        => 'WIT-' . strtoupper(Str::random(8)),
                'metadata'         => ['seeded' => true],
            ]);
        }
    }
}
