<?php

namespace Tests\Property;

use App\Models\User;
use App\Models\Wallet;
use Eris\Generator;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: forex-broker-platform, Property 18: Investment plan validation enforces all constraints.
 */
class PlanPropertyTest extends TestCase
{
    use RefreshDatabase;
    use TestTrait;

    /**
     * P18: For any plan payload violating min/max/ROI constraints, assert 422
     * with field-level errors for each violated constraint.
     */
    public function test_p18_plan_validation_enforces_all_constraints(): void
    {
        $admin = User::factory()->admin()->create();
        Wallet::create(['user_id' => $admin->id, 'balance_cents' => 0]);

        // Case 1: min_amount_cents <= 0
        $this->forAll(Generator\choose(-10000, 0))
            ->then(function (int $invalidMin) use ($admin) {
                $response = $this->actingAs($admin)->postJson('/api/v1/admin/plans', [
                    'name'             => 'Test Plan',
                    'min_amount_cents' => $invalidMin,
                    'max_amount_cents' => 100000,
                    'roi_percentage'   => 10,
                    'profit_min_pct'   => 1,
                    'profit_max_pct'   => 10,
                ]);

                $this->assertEquals(422, $response->status(),
                    "min_amount_cents={$invalidMin} should fail validation"
                );
                $this->assertArrayHasKey('min_amount_cents', $response->json('errors') ?? []);
            });

        // Case 2: max_amount_cents <= min_amount_cents
        $this->forAll(
            Generator\choose(1000, 100000),
            Generator\choose(0, 999)
        )
        ->then(function (int $min, int $offset) use ($admin) {
            $max = $min - $offset; // max <= min

            $response = $this->actingAs($admin)->postJson('/api/v1/admin/plans', [
                'name'             => 'Test Plan',
                'min_amount_cents' => $min,
                'max_amount_cents' => $max,
                'roi_percentage'   => 10,
                'profit_min_pct'   => 1,
                'profit_max_pct'   => 10,
            ]);

            $this->assertEquals(422, $response->status(),
                "max={$max} <= min={$min} should fail validation"
            );
        });

        // Case 3: roi_percentage > 1000
        $this->forAll(Generator\choose(1001, 9999))
            ->then(function (int $invalidRoi) use ($admin) {
                $response = $this->actingAs($admin)->postJson('/api/v1/admin/plans', [
                    'name'             => 'Test Plan',
                    'min_amount_cents' => 10000,
                    'max_amount_cents' => 100000,
                    'roi_percentage'   => $invalidRoi,
                    'profit_min_pct'   => 1,
                    'profit_max_pct'   => 10,
                ]);

                $this->assertEquals(422, $response->status(),
                    "roi_percentage={$invalidRoi} should fail validation"
                );
                $this->assertArrayHasKey('roi_percentage', $response->json('errors') ?? []);
            });
    }
}
