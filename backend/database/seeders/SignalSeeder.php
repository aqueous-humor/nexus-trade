<?php

namespace Database\Seeders;

use App\Models\Signal;
use App\Models\User;
use Illuminate\Database\Seeder;

class SignalSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();

        $signals = [
            [
                'name'              => 'AlphaScalper',
                'description'       => 'High-frequency scalping strategy targeting short-term price movements.',
                'status'            => 'active',
                'provider_metadata' => [
                    'strategy'   => 'scalping',
                    'timeframe'  => 'M1',
                    'pairs'      => ['EUR/USD', 'GBP/USD'],
                    'win_rate'   => 72.5,
                ],
            ],
            [
                'name'              => 'BetaTrend',
                'description'       => 'Trend-following strategy using moving average crossovers.',
                'status'            => 'active',
                'provider_metadata' => [
                    'strategy'   => 'trend_following',
                    'timeframe'  => 'H4',
                    'pairs'      => ['USD/JPY', 'AUD/USD'],
                    'win_rate'   => 65.0,
                ],
            ],
            [
                'name'              => 'GammaSwing',
                'description'       => 'Swing trading strategy capturing medium-term price swings.',
                'status'            => 'active',
                'provider_metadata' => [
                    'strategy'   => 'swing_trading',
                    'timeframe'  => 'D1',
                    'pairs'      => ['EUR/GBP', 'USD/CHF'],
                    'win_rate'   => 58.3,
                ],
            ],
        ];

        foreach ($signals as $signalData) {
            Signal::firstOrCreate(
                ['name' => $signalData['name']],
                array_merge($signalData, ['created_by' => $admin?->id])
            );
        }
    }
}
