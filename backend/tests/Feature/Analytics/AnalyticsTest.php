<?php

namespace Tests\Feature\Analytics;

use App\Models\Account;
use App\Models\Duration;
use App\Models\Investment;
use App\Models\InvestmentPlan;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private function seedInvestments(User $user, Account $account, InvestmentPlan $plan, Duration $duration): void
    {
        // 2 completed WIN investments
        Investment::factory()->count(2)->create([
            'user_id'      => $user->id,
            'account_id'   => $account->id,
            'plan_id'      => $plan->id,
            'duration_id'  => $duration->id,
            'amount_cents' => 100000,
            'profit_cents' => 10000,
            'status'       => 'completed',
            'result'       => 'WIN',
        ]);

        // 1 active investment
        Investment::factory()->create([
            'user_id'     => $user->id,
            'account_id'  => $account->id,
            'plan_id'     => $plan->id,
            'duration_id' => $duration->id,
            'amount_cents'=> 50000,
            'status'      => 'active',
        ]);
    }

    // ----------------------------------------------------------------
    // User metrics
    // ----------------------------------------------------------------

    public function test_user_metrics_returns_correct_totals(): void
    {
        $user     = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);
        $account  = Account::factory()->create(['user_id' => $user->id]);
        $plan     = InvestmentPlan::factory()->create(['roi_percentage' => 10]);
        $duration = Duration::factory()->daily()->create();

        $this->seedInvestments($user, $account, $plan, $duration);

        $response = $this->actingAs($user)->getJson('/api/v1/analytics/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.total_invested_cents', 250000)  // 2×100k + 1×50k
            ->assertJsonPath('data.total_profit_cents', 20000)     // 2×10k WIN
            ->assertJsonPath('data.active_investments', 1)
            ->assertJsonStructure(['data' => ['roi_percentage', 'plan_distribution', 'computed_at']]);
    }

    public function test_user_metrics_roi_is_zero_with_no_investments(): void
    {
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);

        $response = $this->actingAs($user)->getJson('/api/v1/analytics/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.total_invested_cents', 0)
            ->assertJsonPath('data.roi_percentage', 0);
    }

    // ----------------------------------------------------------------
    // Time series
    // ----------------------------------------------------------------

    public function test_user_timeseries_returns_data_for_date_range(): void
    {
        $user     = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);
        $account  = Account::factory()->create(['user_id' => $user->id]);
        $plan     = InvestmentPlan::factory()->create();
        $duration = Duration::factory()->daily()->create();

        Investment::factory()->create([
            'user_id'     => $user->id,
            'account_id'  => $account->id,
            'plan_id'     => $plan->id,
            'duration_id' => $duration->id,
            'status'      => 'completed',
            'created_at'  => now()->subDays(5),
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/analytics/me/timeseries?granularity=daily');

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    public function test_timeseries_rejects_invalid_granularity(): void
    {
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);

        $response = $this->actingAs($user)->getJson('/api/v1/analytics/me/timeseries?granularity=hourly');

        $response->assertStatus(422);
    }

    // ----------------------------------------------------------------
    // Admin platform metrics
    // ----------------------------------------------------------------

    public function test_admin_can_view_platform_metrics(): void
    {
        $admin = User::factory()->admin()->create();
        Wallet::create(['user_id' => $admin->id, 'balance_cents' => 0]);

        $response = $this->actingAs($admin)->getJson('/api/v1/admin/analytics');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => [
                'total_investments',
                'total_invested_usd',
                'total_profit_paid_usd',
                'active_users',
                'top_plans',
                'computed_at',
            ]]);
    }

    public function test_non_admin_cannot_view_platform_metrics(): void
    {
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);

        $response = $this->actingAs($user)->getJson('/api/v1/admin/analytics');

        $response->assertStatus(403);
    }

    // ----------------------------------------------------------------
    // Plan distribution
    // ----------------------------------------------------------------

    public function test_plan_distribution_percentages_sum_to_100(): void
    {
        $user     = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);
        $account  = Account::factory()->create(['user_id' => $user->id]);
        $plan1    = InvestmentPlan::factory()->create();
        $plan2    = InvestmentPlan::factory()->create();
        $duration = Duration::factory()->daily()->create();

        Investment::factory()->create(['user_id' => $user->id, 'account_id' => $account->id, 'plan_id' => $plan1->id, 'duration_id' => $duration->id, 'amount_cents' => 60000, 'status' => 'completed']);
        Investment::factory()->create(['user_id' => $user->id, 'account_id' => $account->id, 'plan_id' => $plan2->id, 'duration_id' => $duration->id, 'amount_cents' => 40000, 'status' => 'completed']);

        $response = $this->actingAs($user)->getJson('/api/v1/analytics/me');

        $distribution = $response->json('data.plan_distribution');
        $total = array_sum(array_column($distribution, 'percentage'));

        $this->assertEqualsWithDelta(100.0, $total, 0.01);
    }
}
