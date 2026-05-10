<?php

namespace Tests\Feature\Investment;

use App\Models\Account;
use App\Models\Duration;
use App\Models\Investment;
use App\Models\InvestmentPlan;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanTest extends TestCase
{
    use RefreshDatabase;

    // ----------------------------------------------------------------
    // User-facing plan listing
    // ----------------------------------------------------------------

    public function test_plans_are_returned_trending_first(): void
    {
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);

        InvestmentPlan::factory()->create(['name' => 'Regular Plan', 'is_trending' => false]);
        InvestmentPlan::factory()->trending()->create(['name' => 'Trending Plan']);

        $response = $this->actingAs($user)->getJson('/api/v1/plans');

        $response->assertStatus(200);
        $this->assertEquals('Trending Plan', $response->json('data.0.name'));
    }

    public function test_inactive_plans_are_excluded_from_listing(): void
    {
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);

        InvestmentPlan::factory()->create(['status' => 'active']);
        InvestmentPlan::factory()->create(['status' => 'inactive']);

        $response = $this->actingAs($user)->getJson('/api/v1/plans');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_plan_show_includes_durations(): void
    {
        $user     = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);
        $plan     = InvestmentPlan::factory()->create();
        $duration = Duration::factory()->daily()->create();
        $plan->durations()->attach($duration->id);

        $response = $this->actingAs($user)->getJson("/api/v1/plans/{$plan->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.durations');
    }

    // ----------------------------------------------------------------
    // Admin plan CRUD
    // ----------------------------------------------------------------

    public function test_admin_can_create_plan(): void
    {
        $admin = User::factory()->admin()->create();
        Wallet::create(['user_id' => $admin->id, 'balance_cents' => 0]);

        $response = $this->actingAs($admin)->postJson('/api/v1/admin/plans', [
            'name'             => 'Gold Plan',
            'min_amount_cents' => 10000,
            'max_amount_cents' => 500000,
            'roi_percentage'   => 15.5,
            'profit_min_pct'   => 5,
            'profit_max_pct'   => 20,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Gold Plan');
    }

    public function test_admin_can_create_plan_with_durations(): void
    {
        $admin    = User::factory()->admin()->create();
        Wallet::create(['user_id' => $admin->id, 'balance_cents' => 0]);
        $duration = Duration::factory()->daily()->create();

        $response = $this->actingAs($admin)->postJson('/api/v1/admin/plans', [
            'name'             => 'Silver Plan',
            'min_amount_cents' => 5000,
            'max_amount_cents' => 100000,
            'roi_percentage'   => 10,
            'profit_min_pct'   => 2,
            'profit_max_pct'   => 15,
            'duration_ids'     => [$duration->id],
        ]);

        $response->assertStatus(201)
            ->assertJsonCount(1, 'data.durations');
    }

    public function test_plan_creation_fails_with_invalid_min_amount(): void
    {
        $admin = User::factory()->admin()->create();
        Wallet::create(['user_id' => $admin->id, 'balance_cents' => 0]);

        $response = $this->actingAs($admin)->postJson('/api/v1/admin/plans', [
            'name'             => 'Bad Plan',
            'min_amount_cents' => 0,
            'max_amount_cents' => 100000,
            'roi_percentage'   => 10,
            'profit_min_pct'   => 1,
            'profit_max_pct'   => 10,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrorFor('min_amount_cents');
    }

    public function test_plan_creation_fails_when_max_less_than_min(): void
    {
        $admin = User::factory()->admin()->create();
        Wallet::create(['user_id' => $admin->id, 'balance_cents' => 0]);

        $response = $this->actingAs($admin)->postJson('/api/v1/admin/plans', [
            'name'             => 'Bad Plan',
            'min_amount_cents' => 100000,
            'max_amount_cents' => 50000,
            'roi_percentage'   => 10,
            'profit_min_pct'   => 1,
            'profit_max_pct'   => 10,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrorFor('max_amount_cents');
    }

    public function test_plan_creation_fails_with_roi_over_1000(): void
    {
        $admin = User::factory()->admin()->create();
        Wallet::create(['user_id' => $admin->id, 'balance_cents' => 0]);

        $response = $this->actingAs($admin)->postJson('/api/v1/admin/plans', [
            'name'             => 'Bad Plan',
            'min_amount_cents' => 10000,
            'max_amount_cents' => 100000,
            'roi_percentage'   => 1001,
            'profit_min_pct'   => 1,
            'profit_max_pct'   => 10,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrorFor('roi_percentage');
    }

    public function test_soft_delete_preserves_investment_records(): void
    {
        $admin    = User::factory()->admin()->create();
        $user     = User::factory()->create();
        Wallet::create(['user_id' => $admin->id, 'balance_cents' => 0]);
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);

        $plan     = InvestmentPlan::factory()->create();
        $duration = Duration::factory()->daily()->create();
        $account  = Account::factory()->create(['user_id' => $user->id]);

        $investment = Investment::factory()->create([
            'user_id'     => $user->id,
            'account_id'  => $account->id,
            'plan_id'     => $plan->id,
            'duration_id' => $duration->id,
        ]);

        $this->actingAs($admin)->deleteJson("/api/v1/admin/plans/{$plan->id}");

        // Plan is soft-deleted
        $this->assertSoftDeleted('investment_plans', ['id' => $plan->id]);

        // Investment record still exists
        $this->assertDatabaseHas('investments', ['id' => $investment->id]);
    }

    public function test_admin_can_mark_plan_as_trending(): void
    {
        $admin = User::factory()->admin()->create();
        Wallet::create(['user_id' => $admin->id, 'balance_cents' => 0]);
        $plan  = InvestmentPlan::factory()->create(['is_trending' => false]);

        $response = $this->actingAs($admin)->patchJson("/api/v1/admin/plans/{$plan->id}", [
            'is_trending'          => true,
            'trending_image_url'   => 'https://example.com/image.jpg',
            'trending_title'       => 'Hot Plan',
            'trending_description' => 'Best returns this month',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.is_trending', true);
    }

    public function test_non_admin_cannot_access_admin_plan_endpoints(): void
    {
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);

        $response = $this->actingAs($user)->postJson('/api/v1/admin/plans', [
            'name' => 'Sneaky Plan',
        ]);

        $response->assertStatus(403);
    }
}
