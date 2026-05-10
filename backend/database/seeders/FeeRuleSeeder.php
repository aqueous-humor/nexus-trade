<?php

namespace Database\Seeders;

use App\Models\FeeRule;
use Illuminate\Database\Seeder;

class FeeRuleSeeder extends Seeder
{
    public function run(): void
    {
        $feeRules = [
            // Binance
            [
                'provider'         => 'binance',
                'transaction_type' => 'deposit',
                'fee_type'         => 'percentage',
                'fee_value'        => 0.1,
            ],
            [
                'provider'         => 'binance',
                'transaction_type' => 'withdrawal',
                'fee_type'         => 'percentage',
                'fee_value'        => 0.2,
            ],
            // KuCoin
            [
                'provider'         => 'kucoin',
                'transaction_type' => 'deposit',
                'fee_type'         => 'percentage',
                'fee_value'        => 0.15,
            ],
            [
                'provider'         => 'kucoin',
                'transaction_type' => 'withdrawal',
                'fee_type'         => 'fixed',
                'fee_value'        => 200, // $2.00 in cents
            ],
            // XT
            [
                'provider'         => 'xt',
                'transaction_type' => 'deposit',
                'fee_type'         => 'percentage',
                'fee_value'        => 0.1,
            ],
            [
                'provider'         => 'xt',
                'transaction_type' => 'withdrawal',
                'fee_type'         => 'fixed',
                'fee_value'        => 150, // $1.50 in cents
            ],
        ];

        foreach ($feeRules as $rule) {
            FeeRule::firstOrCreate(
                [
                    'provider'         => $rule['provider'],
                    'transaction_type' => $rule['transaction_type'],
                ],
                [
                    'fee_type'  => $rule['fee_type'],
                    'fee_value' => $rule['fee_value'],
                ]
            );
        }
    }
}
