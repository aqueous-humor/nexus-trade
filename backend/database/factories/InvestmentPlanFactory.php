<?php

namespace Database\Factories;

use App\Models\InvestmentPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InvestmentPlan> */
class InvestmentPlanFactory extends Factory
{
    public function definition(): array
    {
        $min = fake()->numberBetween(10000, 50000);   // $100–$500
        $max = $min * fake()->numberBetween(5, 20);   // 5–20x min

        return [
            'name'                => fake()->words(3, true) . ' Plan',
            'description'         => fake()->sentence(),
            'min_amount_cents'    => $min,
            'max_amount_cents'    => $max,
            'roi_percentage'      => fake()->randomFloat(2, 1, 50),
            'profit_min_pct'      => fake()->randomFloat(2, 1, 10),
            'profit_max_pct'      => fake()->randomFloat(2, 10, 50),
            'is_trending'         => false,
            'status'              => 'active',
        ];
    }

    public function trending(): static
    {
        return $this->state(fn () => [
            'is_trending'         => true,
            'trending_image_url'  => fake()->imageUrl(),
            'trending_title'      => fake()->sentence(4),
            'trending_description'=> fake()->paragraph(),
        ]);
    }
}
