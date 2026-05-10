<?php

namespace Tests\Feature\Wallet;

use App\Models\FeeRule;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepositTest extends TestCase
{
    use RefreshDatabase;

    // ----------------------------------------------------------------
    // Initiate deposit
    // ----------------------------------------------------------------

    public function test_user_can_initiate_fiat_deposit(): void
    {
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);

        $response = $this->actingAs($user)->postJson('/api/v1/wallet/deposit', [
            'currency' => 'USD',
            'amount'   => '500.00',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['transaction_id', 'exchange_rate', 'estimated_usd', 'net_usd']]);

        $this->assertDatabaseHas('transactions', [
            'user_id'  => $user->id,
            'type'     => 'deposit',
            'status'   => 'pending',
            'currency' => 'USD',
        ]);
    }

    public function test_user_can_initiate_crypto_deposit(): void
    {
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);

        $response = $this->actingAs($user)->postJson('/api/v1/wallet/deposit', [
            'currency' => 'ETH',
            'network'  => 'ERC-20',
            'amount'   => '0.5',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['transaction_id', 'wallet_address', 'network', 'network_fee_usd']]);
    }

    public function test_deposit_with_provider_fee_records_fee(): void
    {
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);

        FeeRule::create([
            'provider'         => 'binance',
            'transaction_type' => 'deposit',
            'fee_type'         => 'percentage',
            'fee_value'        => 1.0, // 1%
        ]);

        $response = $this->actingAs($user)->postJson('/api/v1/wallet/deposit', [
            'currency' => 'USDT',
            'network'  => 'BEP-20',
            'provider' => 'binance',
            'amount'   => '1000.00',
        ]);

        $response->assertStatus(201);
        $txId = $response->json('data.transaction_id');
        $tx   = Transaction::find($txId);

        // 1% of $1000 = $10 fee
        $this->assertEquals(1000, $tx->fee_cents);
        $this->assertEquals(99000, $tx->net_amount_cents);
    }

    public function test_deposit_rejects_invalid_currency(): void
    {
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);

        $response = $this->actingAs($user)->postJson('/api/v1/wallet/deposit', [
            'currency' => 'XYZ',
            'amount'   => '100.00',
        ]);

        $response->assertStatus(422);
    }

    public function test_deposit_rejects_invalid_network_for_currency(): void
    {
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);

        $response = $this->actingAs($user)->postJson('/api/v1/wallet/deposit', [
            'currency' => 'BTC',
            'network'  => 'ERC-20', // BTC doesn't support ERC-20
            'amount'   => '0.1',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 'INVALID_NETWORK');
    }

    // ----------------------------------------------------------------
    // Confirm deposit
    // ----------------------------------------------------------------

    public function test_confirming_deposit_credits_wallet(): void
    {
        $user   = User::factory()->create();
        $wallet = Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);

        // Initiate
        $initResponse = $this->actingAs($user)->postJson('/api/v1/wallet/deposit', [
            'currency' => 'USD',
            'amount'   => '200.00',
        ]);
        $txId = $initResponse->json('data.transaction_id');

        // Confirm
        $response = $this->actingAs($user)->postJson("/api/v1/wallet/deposit/{$txId}/confirm");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'completed');

        // Wallet balance should be credited
        $this->assertGreaterThan(0, $wallet->fresh()->balance_cents);
    }

    public function test_currency_conversion_is_recorded(): void
    {
        $user   = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);

        $initResponse = $this->actingAs($user)->postJson('/api/v1/wallet/deposit', [
            'currency' => 'EUR',
            'amount'   => '100.00',
        ]);

        $txId = $initResponse->json('data.transaction_id');
        $tx   = Transaction::find($txId);

        // EUR rate is 1.08 — $100 EUR = $108 USD
        $this->assertEquals('EUR', $tx->currency);
        $this->assertNotNull($tx->exchange_rate);
        $this->assertEquals(10800, $tx->amount_cents); // $108.00
    }

    // ----------------------------------------------------------------
    // Rate limiting
    // ----------------------------------------------------------------

    public function test_deposit_rate_limit_is_enforced(): void
    {
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);

        // Make 5 deposits (the limit)
        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user)->postJson('/api/v1/wallet/deposit', [
                'currency' => 'USD',
                'amount'   => '10.00',
            ]);
        }

        // 6th should be rate limited
        $response = $this->actingAs($user)->postJson('/api/v1/wallet/deposit', [
            'currency' => 'USD',
            'amount'   => '10.00',
        ]);

        $response->assertStatus(429)
            ->assertJsonPath('code', 'RATE_LIMIT_EXCEEDED');
    }
}
