<?php

namespace Database\Factories;

use App\Models\Broker;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Broker> */
class BrokerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'                   => fake()->company() . ' Broker',
            'platform_type'          => fake()->randomElement(['MT4', 'MT5']),
            'connection_credentials' => ['host' => fake()->ipv4(), 'port' => 443, 'api_key' => fake()->uuid()],
            'default_leverage'       => fake()->randomElement([100, 200, 500]),
            'status'                 => 'active',
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => 'inactive']);
    }
}
