<?php

namespace Tests\Feature\Signal;

use App\Models\Account;
use App\Models\Signal;
use App\Models\SignalSubscription;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SignalTest extends TestCase
{
    use RefreshDatabase;

    // ----------------------------------------------------------------
    // List signals
    // ----------------------------------------------------------------

    public function test_user_can_list_active_signals(): void
    {
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);

        Signal::factory()->create(['status' => 'active']);
        Signal::factory()->create(['status' => 'inactive']);

        $response = $this->actingAs($user)->getJson('/api/v1/signals');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    // ----------------------------------------------------------------
    // Subscribe
    // ----------------------------------------------------------------

    public function test_user_can_subscribe_account_to_active_signal(): void
    {
        $user    = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);
        $account = Account::factory()->create(['user_id' => $user->id]);
        $signal  = Signal::factory()->create(['status' => 'active']);

        $response = $this->actingAs($user)->postJson("/api/v1/accounts/{$account->id}/signal", [
            'signal_id' => $signal->id,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('signal_subscriptions', [
            'account_id'      => $account->id,
            'signal_id'       => $signal->id,
            'unsubscribed_at' => null,
        ]);
    }

    public function test_subscribing_replaces_existing_subscription(): void
    {
        $user     = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);
        $account  = Account::factory()->create(['user_id' => $user->id]);
        $signal1  = Signal::factory()->create(['status' => 'active']);
        $signal2  = Signal::factory()->create(['status' => 'active']);

        // Subscribe to signal1
        SignalSubscription::create(['account_id' => $account->id, 'signal_id' => $signal1->id]);

        // Subscribe to signal2 — should unsubscribe from signal1
        $this->actingAs($user)->postJson("/api/v1/accounts/{$account->id}/signal", [
            'signal_id' => $signal2->id,
        ]);

        // signal1 subscription should be closed
        $this->assertDatabaseMissing('signal_subscriptions', [
            'account_id'      => $account->id,
            'signal_id'       => $signal1->id,
            'unsubscribed_at' => null,
        ]);

        // signal2 subscription should be active
        $this->assertDatabaseHas('signal_subscriptions', [
            'account_id'      => $account->id,
            'signal_id'       => $signal2->id,
            'unsubscribed_at' => null,
        ]);
    }

    public function test_subscribing_to_inactive_signal_is_rejected(): void
    {
        $user    = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);
        $account = Account::factory()->create(['user_id' => $user->id]);
        $signal  = Signal::factory()->create(['status' => 'inactive']);

        $response = $this->actingAs($user)->postJson("/api/v1/accounts/{$account->id}/signal", [
            'signal_id' => $signal->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 'SIGNAL_INACTIVE');
    }

    public function test_user_cannot_subscribe_another_users_account(): void
    {
        $user1   = User::factory()->create();
        $user2   = User::factory()->create();
        Wallet::create(['user_id' => $user1->id, 'balance_cents' => 0]);
        Wallet::create(['user_id' => $user2->id, 'balance_cents' => 0]);
        $account = Account::factory()->create(['user_id' => $user2->id]);
        $signal  = Signal::factory()->create(['status' => 'active']);

        $response = $this->actingAs($user1)->postJson("/api/v1/accounts/{$account->id}/signal", [
            'signal_id' => $signal->id,
        ]);

        $response->assertStatus(403);
    }

    // ----------------------------------------------------------------
    // Unsubscribe
    // ----------------------------------------------------------------

    public function test_user_can_unsubscribe_from_signal(): void
    {
        $user    = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);
        $account = Account::factory()->create(['user_id' => $user->id]);
        $signal  = Signal::factory()->create(['status' => 'active']);
        SignalSubscription::create(['account_id' => $account->id, 'signal_id' => $signal->id]);

        $response = $this->actingAs($user)->deleteJson("/api/v1/accounts/{$account->id}/signal");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('signal_subscriptions', [
            'account_id'      => $account->id,
            'unsubscribed_at' => null,
        ]);
    }

    // ----------------------------------------------------------------
    // Admin deactivation auto-unsubscribes
    // ----------------------------------------------------------------

    public function test_deactivating_signal_auto_unsubscribes_all_accounts(): void
    {
        Queue::fake();

        $admin   = User::factory()->admin()->create();
        Wallet::create(['user_id' => $admin->id, 'balance_cents' => 0]);
        $signal  = Signal::factory()->create(['status' => 'active', 'created_by' => $admin->id]);

        $user1   = User::factory()->create();
        $user2   = User::factory()->create();
        Wallet::create(['user_id' => $user1->id, 'balance_cents' => 0]);
        Wallet::create(['user_id' => $user2->id, 'balance_cents' => 0]);
        $account1 = Account::factory()->create(['user_id' => $user1->id]);
        $account2 = Account::factory()->create(['user_id' => $user2->id]);

        SignalSubscription::create(['account_id' => $account1->id, 'signal_id' => $signal->id]);
        SignalSubscription::create(['account_id' => $account2->id, 'signal_id' => $signal->id]);

        $response = $this->actingAs($admin)->postJson("/api/v1/admin/signals/{$signal->id}/deactivate");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'inactive');

        // Job dispatched to handle unsubscriptions
        Queue::assertPushed(\App\Jobs\SignalDeactivatedJob::class);
    }

    // ----------------------------------------------------------------
    // Admin signal CRUD
    // ----------------------------------------------------------------

    public function test_admin_can_create_signal(): void
    {
        $admin = User::factory()->admin()->create();
        Wallet::create(['user_id' => $admin->id, 'balance_cents' => 0]);

        $response = $this->actingAs($admin)->postJson('/api/v1/admin/signals', [
            'name'        => 'EUR/USD Scalper',
            'description' => 'High-frequency EUR/USD signal',
            'status'      => 'active',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'EUR/USD Scalper');
    }

    public function test_admin_cannot_create_duplicate_active_signal_name(): void
    {
        $admin = User::factory()->admin()->create();
        Wallet::create(['user_id' => $admin->id, 'balance_cents' => 0]);
        Signal::factory()->create(['name' => 'EUR/USD Scalper', 'status' => 'active', 'created_by' => $admin->id]);

        $response = $this->actingAs($admin)->postJson('/api/v1/admin/signals', [
            'name'   => 'EUR/USD Scalper',
            'status' => 'active',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrorFor('name');
    }
}
