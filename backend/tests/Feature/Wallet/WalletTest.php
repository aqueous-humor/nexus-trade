<?php

namespace Tests\Feature\Wallet;

use App\Models\User;
use App\Models\Wallet;
use App\Services\WalletService;
use App\Values\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletTest extends TestCase
{
    use RefreshDatabase;

    // ----------------------------------------------------------------
    // GET /api/v1/wallet
    // ----------------------------------------------------------------

    public function test_authenticated_user_can_view_wallet(): void
    {
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 50000]); // $500

        $response = $this->actingAs($user)->getJson('/api/v1/wallet');

        $response->assertStatus(200)
            ->assertJsonPath('data.balance_cents', 50000)
            ->assertJsonPath('data.balance', '500.00')
            ->assertJsonPath('data.currency', 'USD');
    }

    public function test_wallet_returns_401_when_unauthenticated(): void
    {
        $response = $this->getJson('/api/v1/wallet');
        $response->assertStatus(401);
    }

    // ----------------------------------------------------------------
    // GET /api/v1/wallet/transactions
    // ----------------------------------------------------------------

    public function test_user_can_list_transaction_history(): void
    {
        $user    = User::factory()->create();
        $wallet  = Wallet::create(['user_id' => $user->id, 'balance_cents' => 100000]);
        $service = app(WalletService::class);

        // Create 3 transactions
        $service->credit($user->id, Money::fromCents(50000), 'deposit');
        $service->credit($user->id, Money::fromCents(30000), 'profit');
        $service->debit($user->id, Money::fromCents(10000), 'withdrawal');

        $response = $this->actingAs($user)->getJson('/api/v1/wallet/transactions');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']]);
    }

    public function test_transaction_history_can_be_filtered_by_type(): void
    {
        $user   = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 100000]);
        $service = app(WalletService::class);

        $service->credit($user->id, Money::fromCents(50000), 'deposit');
        $service->credit($user->id, Money::fromCents(30000), 'profit');

        $response = $this->actingAs($user)->getJson('/api/v1/wallet/transactions?type=deposit');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_transaction_history_rejects_invalid_type_filter(): void
    {
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);

        $response = $this->actingAs($user)->getJson('/api/v1/wallet/transactions?type=invalid_type');

        $response->assertStatus(422);
    }

    // ----------------------------------------------------------------
    // WalletService unit-level feature tests
    // ----------------------------------------------------------------

    public function test_credit_increases_balance_correctly(): void
    {
        $user    = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 10000]);
        $service = app(WalletService::class);

        $service->credit($user->id, Money::fromCents(5000), 'deposit');

        $this->assertEquals(15000, $service->balance($user->id)->cents);
    }

    public function test_debit_decreases_balance_correctly(): void
    {
        $user    = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 10000]);
        $service = app(WalletService::class);

        $service->debit($user->id, Money::fromCents(3000), 'withdrawal');

        $this->assertEquals(7000, $service->balance($user->id)->cents);
    }

    public function test_debit_throws_when_balance_insufficient(): void
    {
        $user    = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 1000]);
        $service = app(WalletService::class);

        $this->expectException(\App\Exceptions\InsufficientFundsException::class);

        $service->debit($user->id, Money::fromCents(5000), 'withdrawal');
    }

    public function test_debit_does_not_change_balance_on_failure(): void
    {
        $user    = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 1000]);
        $service = app(WalletService::class);

        try {
            $service->debit($user->id, Money::fromCents(5000), 'withdrawal');
        } catch (\App\Exceptions\InsufficientFundsException) {
            // expected
        }

        $this->assertEquals(1000, $service->balance($user->id)->cents);
    }
}
