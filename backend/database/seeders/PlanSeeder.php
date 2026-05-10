<?php

namespace Database\Seeders;

use App\Models\InvestmentPlan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name'             => 'Starter',
                'description'      => 'A beginner-friendly plan with steady returns.',
                'roi_percentage'   => 5.0,
                'min_amount_cents' => 10_000,   // $100
                'max_amount_cents' => 100_000,  // $1,000
                'status'           => 'active',
                'is_trending'      => false,
            ],
            [
                'name'                  => 'Growth',
                'description'           => 'Accelerate your portfolio with higher returns.',
                'roi_percentage'        => 10.0,
                'min_amount_cents'      => 50_000,   // $500
                'max_amount_cents'      => 500_000,  // $5,000
                'status'                => 'active',
                'is_trending'           => true,
                'trending_image_url'    => 'https://images.unsplash.com/photo-1611974789855-9c2a0a7236a3?w=400',
                'trending_title'        => 'Most Popular',
                'trending_description'  => 'Join thousands of investors growing their wealth.',
            ],
            [
                'name'                  => 'Premium',
                'description'           => 'Premium returns for serious investors.',
                'roi_percentage'        => 15.0,
                'min_amount_cents'      => 100_000,   // $1,000
                'max_amount_cents'      => 1_000_000, // $10,000
                'status'                => 'active',
                'is_trending'           => true,
                'trending_image_url'    => 'https://images.unsplash.com/photo-1559526324-4b87b5e36e44?w=400',
                'trending_title'        => 'Best Value',
                'trending_description'  => 'Maximum returns with managed risk.',
            ],
            [
                'name'             => 'Elite',
                'description'      => 'Exclusive high-yield plan for elite investors.',
                'roi_percentage'   => 20.0,
                'min_amount_cents' => 500_000,   // $5,000
                'max_amount_cents' => 5_000_000, // $50,000
                'status'           => 'active',
                'is_trending'      => false,
            ],
            [
                'name'             => 'Legacy',
                'description'      => 'A legacy plan no longer accepting new investments.',
                'roi_percentage'   => 8.0,
                'min_amount_cents' => 20_000,   // $200
                'max_amount_cents' => 200_000,  // $2,000
                'status'           => 'inactive',
                'is_trending'      => false,
            ],
        ];

        foreach ($plans as $planData) {
            InvestmentPlan::firstOrCreate(
                ['name' => $planData['name']],
                $planData
            );
        }
    }
}
