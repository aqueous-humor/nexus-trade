<?php

namespace Database\Factories;

use App\Models\Duration;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Duration> */
class DurationFactory extends Factory
{
    private static int $counter = 0;

    public function definition(): array
    {
        // Use a counter to ensure unique unit+value combinations
        self::$counter++;
        $units  = ['hour', 'day', 'week', 'month'];
        $unit   = $units[self::$counter % 4];
        $value  = self::$counter;
        $labels = ['hour' => 'Hour', 'day' => 'Day', 'week' => 'Week', 'month' => 'Month'];

        return [
            'unit'  => $unit,
            'value' => $value,
            'label' => "{$value} {$labels[$unit]}",
        ];
    }

    public function daily(): static
    {
        return $this->state(fn () => ['unit' => 'day', 'value' => 1, 'label' => '1 Day']);
    }
}
