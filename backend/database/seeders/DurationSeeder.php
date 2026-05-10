<?php

namespace Database\Seeders;

use App\Models\Duration;
use App\Models\InvestmentPlan;
use Illuminate\Database\Seeder;

class DurationSeeder extends Seeder
{
    public function run(): void
    {
        $durations = [
            ['unit' => 'hour',  'value' => 1,  'label' => '1 Hour'],
            ['unit' => 'hour',  'value' => 4,  'label' => '4 Hours'],
            ['unit' => 'day',   'value' => 1,  'label' => '1 Day'],
            ['unit' => 'day',   'value' => 7,  'label' => '7 Days'],
            ['unit' => 'day',   'value' => 30, 'label' => '30 Days'],
        ];

        $createdDurations = [];
        foreach ($durations as $durationData) {
            $duration = Duration::firstOrCreate(
                ['unit' => $durationData['unit'], 'value' => $durationData['value']],
                ['label' => $durationData['label']]
            );
            $createdDurations[] = $duration;
        }

        // Link all active plans to all durations via plan_durations pivot
        $activePlans = InvestmentPlan::where('status', 'active')->get();

        foreach ($activePlans as $plan) {
            foreach ($createdDurations as $duration) {
                // Attach only if not already attached
                if (! $plan->durations()->where('duration_id', $duration->id)->exists()) {
                    $plan->durations()->attach($duration->id);
                }
            }
        }
    }
}
