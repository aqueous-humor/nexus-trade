<?php

namespace Tests\Feature\Account;

use App\Models\Account;
use App\Models\Broker;
use App\Models\Duration;
use App\Models\Investment;
use App\Models\InvestmentPlan;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountCrudTest extends TestCase
{
    use RefreshDatabase;

    // ----------------------------------------------------------------
    // Create demo account
    // ----------------------------------------------------------------

    public function test_user_can_create_demo_account(): void
    {
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);

        $response = $this->actingAs($user)->postJson('/api/v1/accounts', [
            'type' => 'demo',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.type', 'demo')
            ->assertJsonPath('data.status', 'active');

        // Demo account gets default $10,000 balance
        $this->assertEquals(1000000, $response->json('data.balance_cents'));
    }

    // ----------------------------------------------------------------
    // Create live account
    // ----------------------------------------------------------------

    public function test_user_can_create_live_account_with_verified_email(): void
    {
        $user   = User::factory()->create(['email_verified_at' => now()]);
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);
        $broker = Broker::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/accounts', [
            'type'              => 'live',
            'broker_id'         => $broker->id,
            'broker_account_id' => 'MT5-123456',
            'leverage'          => 100,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.type', 'live')
            ->assertJsonPath('data.broker_account_id', 'MT5-123456');
    }

    public function test_live_account_requires_verified_email(): void
    {
        $user   = User::factory()->unverified()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);
        $broker = Broker::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/accounts', [
            'type'              => 'live',
            'broker_id'         => $broker->id,
            'broker_account_id' => 'MT5-123456',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('code', 'EMAIL_NOT_VERIFIED');
    }

    public function test_live_account_requires_broker_id(): void
    {
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);

        $response = $this->actingAs($user)->postJson('/api/v1/accounts', [
            'type' => 'live',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrorFor('broker_id');
    }

    public function test_live_account_rejects_inactive_broker(): void
    {
        $user   = User::factory()->create(['email_verified_at' => now()]);
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);
        $broker = Broker::factory()->inactive()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/accounts', [
            'type'              => 'live',
            'broker_id'         => $broker->id,
            'broker_account_id' => 'MT5-123456',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 'BROKER_INACTIVE');
    }

    // ----------------------------------------------------------------
    // List & show
    // ----------------------------------------------------------------

    public function test_user_can_list_their_accounts(): void
    {
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);
        Account::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson('/api/v1/accounts');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_user_cannot_view_another_users_account(): void
    {
        $user1   = User::factory()->create();
        $user2   = User::factory()->create();
        Wallet::create(['user_id' => $user1->id, 'balance_cents' => 0]);
        Wallet::create(['user_id' => $user2->id, 'balance_cents' => 0]);
        $account = Account::factory()->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1)->getJson("/api/v1/accounts/{$account->id}");

        $response->assertStatus(403);
    }

    // ----------------------------------------------------------------
    // Soft delete
    // ----------------------------------------------------------------

    public function test_user_can_soft_delete_their_account(): void
    {
        $user    = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);
        $account = Account::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->deleteJson("/api/v1/accounts/{$account->id}");

        $response->assertStatus(204);
        $this->assertSoftDeleted('accounts', ['id' => $account->id]);
    }

    // ----------------------------------------------------------------
    // Leverage
    // ----------------------------------------------------------------

    public function test_user_can_update_leverage_when_no_active_investment(): void
    {
        $user    = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);
        $account = Account::factory()->create(['user_id' => $user->id, 'leverage' => 100]);

        $response = $this->actingAs($user)->patchJson("/api/v1/accounts/{$account->id}/leverage", [
            'leverage' => 200,
        ]);

        $response->assertStatus(200);
        $this->assertEquals(200, $account->fresh()->leverage);
    }

    public function test_leverage_update_blocked_when_active_investment_exists(): void
    {
        $user     = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);
        $account  = Account::factory()->create(['user_id' => $user->id, 'leverage' => 100]);
        $plan     = InvestmentPlan::factory()->create();
        $duration = Duration::factory()->create();

        Investment::factory()->create([
            'user_id'     => $user->id,
            'account_id'  => $account->id,
            'plan_id'     => $plan->id,
            'duration_id' => $duration->id,
            'status'      => 'active',
        ]);

        $response = $this->actingAs($user)->patchJson("/api/v1/accounts/{$account->id}/leverage", [
            'leverage' => 500,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 'LEVERAGE_CHANGE_BLOCKED');

        $this->assertEquals(100, $account->fresh()->leverage);
    }

    public function test_leverage_update_rejects_invalid_value(): void
    {
        $user    = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);
        $account = Account::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->patchJson("/api/v1/accounts/{$account->id}/leverage", [
            'leverage' => 999, // not in allowed set
        ]);

        $response->assertStatus(422);
    }
}
