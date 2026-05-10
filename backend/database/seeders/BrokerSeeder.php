<?php

namespace Database\Seeders;

use App\Models\Broker;
use Illuminate\Database\Seeder;

class BrokerSeeder extends Seeder
{
    public function run(): void
    {
        $brokers = [
            [
                'name'                   => 'AlphaFX',
                'platform_type'          => 'MT4',
                'default_leverage'       => 100,
                'status'                 => 'active',
                'connection_credentials' => ['api_key' => 'alphafx-demo-key'],
            ],
            [
                'name'                   => 'BetaTrade',
                'platform_type'          => 'MT4',
                'default_leverage'       => 200,
                'status'                 => 'active',
                'connection_credentials' => ['api_key' => 'betatrade-demo-key'],
            ],
            [
                'name'                   => 'GammaMarkets',
                'platform_type'          => 'MT5',
                'default_leverage'       => 500,
                'status'                 => 'active',
                'connection_credentials' => ['api_key' => 'gammamarkets-demo-key'],
            ],
        ];

        foreach ($brokers as $brokerData) {
            Broker::firstOrCreate(
                ['name' => $brokerData['name']],
                $brokerData
            );
        }
    }
}
