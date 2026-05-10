<?php

namespace Tests\Property;

use App\Models\Account;
use App\Models\Duration;
use App\Models\Investment;
use App\Models\InvestmentPlan;
use App\Models\User;
use App\Models\Wallet;
use App\Services\AnalyticsEngine;
use Eris\Generator;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: forex-broker-platform, Property 14: Analytics metrics are consistent with investment records.
 */
class AnalyticsPropertyTest extends TestCase
{
    use RefreshDatabase;
    use TestTrait;

    /**
     * P14: For any set of investments, the computed metrics SHALL match the formulas exactly:
     * - total_invested = Σ amount_cents for active/completed investments
     * - total_profit   = Σ profit_cents for completed WIN investments
     * - roi_percentage = (total_profit / total_invested) × 100
     * - active_count   = count of active investments
     */
    public function test_p14_analytics_metrics_consistent_with_investment_records(): void
    {
        $engine = app(AnalyticsEngine::class);

        $this->forAll(
            Generator\choose(1, 5),    // number of completed WIN investments
            Generator\choose(0, 3),    // number of active investments
            Generator\choose(10000, 100000) // amount per investment in cents
        )
        ->then(function (int $winCount, int $activeCount, int $amount) use ($engine) {
            $user     = User::factory()->create();
            Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);
            $account  = Account::factory()->create(['user_id' => $user->id]);
            $plan     = InvestmentPlan::factory()->create(['roi_percentage' => 10]);
            $duration = Duration::firstOrCreate(['unit' => 'day', 'value' => 1], ['label' => '1 Day']);

            $expectedInvested = 0;
            $expectedProfit   = 0;

            // Create WIN investments
            for ($i = 0; $i < $winCount; $i++) {
                $profit = (int) round($amount * 10 / 100);
                Investment::factory()->create([
                    'user_id'      => $user->id,
                    'account_id'   => $account->id,
                    'plan_id'      => $plan->id,
                    'duration_id'  => $duration->id,
                    'amount_cents' => $amount,
                    'profit_cents' => $profit,
                    'status'       => 'completed',
                    'result'       => 'WIN',
                ]);
                $expectedInvested += $amount;
                $expectedProfit   += $profit;
            }

            // Create active investments
            for ($i = 0; $i < $activeCount; $i++) {
                Investment::factory()->create([
                    'user_id'      => $user->id,
                    'account_id'   => $account->id,
                    'plan_id'      => $plan->id,
                    'duration_id'  => $duration->id,
                    'amount_cents' => $amount,
                    'profit_cents' => 0,
                    'status'       => 'active',
                ]);
                $expectedInvested += $amount;
            }

            $expectedRoi = $expectedInvested > 0
                ? round(($expectedProfit / $expectedInvested) * 100, 2)
                : 0.0;

            $metrics = $engine->userMetrics($user->id);

            $this->assertEquals($expectedInvested, $metrics['total_invested_cents'],
                "total_invested_cents should be {$expectedInvested}"
            );
            $this->assertEquals($expectedProfit, $metrics['total_profit_cents'],
                "total_profit_cents should be {$expectedProfit}"
            );
            $this->assertEquals($expectedRoi, $metrics['roi_percentage'],
                "roi_percentage should be {$expectedRoi}"
            );
            $this->assertEquals($activeCount, $metrics['active_investments'],
                "active_investments should be {$activeCount}"
            );
        });
    }
}
