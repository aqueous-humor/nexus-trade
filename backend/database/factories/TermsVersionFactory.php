<?php

namespace Database\Factories;

use App\Models\TermsVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TermsVersion> */
class TermsVersionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'version'      => 'v' . fake()->unique()->numerify('#.#'),
            'content'      => fake()->paragraphs(3, true),
            'effective_at' => now()->subDay(),
        ];
    }
}
