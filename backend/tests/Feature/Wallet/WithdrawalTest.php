<?php

namespace Tests\Feature\Wallet;

use App\Models\User;
use App\Models\Wallet;
use App\Services\WithdrawalLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WithdrawalTest extends TestCase
{
    use RefreshDatabase;

    // ----------------------------------------------------------------
    // Happy path
    // ----------------------------------------------------------------

    public function test_user_can_withdraw_within_limits(): void
    {
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 100000]); // $1,000

        $response = $this->actingAs($user)->postJson('/api/v1/wallet/withdraw', [
            'amount'              => '100.00',
            'destination_address' => '0xABC123',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.amount', '100.00')
            ->assertJsonStructure(['data' => ['transaction_id', 'fee', 'net_amount', 'daily_remaining', 'monthly_remaining']]);

        $this->assertEquals(90000, Wallet::where('user_id', $user->id)->value('balance_cents'));
    }

    // ----------------------------------------------------------------
    // Insufficient balance
    // ----------------------------------------------------------------

    public function test_withdrawal_fails_with_insufficient_balance(): void
    {
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 1000]); // $10

        $response = $this->actingAs($user)->postJson('/api/v1/wallet/withdraw', [
            'amount'              => '500.00',
            'destination_address' => '0xABC123',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 'INSUFFICIENT_FUNDS');
    }

    // ----------------------------------------------------------------
    // Daily limit
    // ----------------------------------------------------------------

    public function test_withdrawal_fails_when_daily_limit_exceeded(): void
    {
        $user    = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 10000000]); // $100,000

        // Pre-populate the daily limit cache key directly
        $dailyKey = "withdrawal:daily:{$user->id}:" . now()->format('Y-m-d');
        \Illuminate\Support\Facades\Cache::put($dailyKey, 490000, 86400); // $4,900 already withdrawn

        // Try to withdraw $200 (would exceed $5,000 daily limit)
        $response = $this->actingAs($user)->postJson('/api/v1/wallet/withdraw', [
            'amount'              => '200.00',
            'destination_address' => '0xABC123',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 'WITHDRAWAL_LIMIT_EXCEEDED');
    }

    public function test_withdrawal_succeeds_at_exact_daily_limit(): void
    {
        $user    = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 10000000]);

        // $4,900 already withdrawn
        $dailyKey = "withdrawal:daily:{$user->id}:" . now()->format('Y-m-d');
        \Illuminate\Support\Facades\Cache::put($dailyKey, 490000, 86400);

        // Withdraw exactly $100 (reaches but does not exceed $5,000)
        $response = $this->actingAs($user)->postJson('/api/v1/wallet/withdraw', [
            'amount'              => '100.00',
            'destination_address' => '0xABC123',
        ]);

        $response->assertStatus(201);
    }

    // ----------------------------------------------------------------
    // Monthly limit
    // ----------------------------------------------------------------

    public function test_withdrawal_fails_when_monthly_limit_exceeded(): void
    {
        $user    = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 100000000]);

        // Simulate $49,900 already withdrawn this month
        $monthlyKey = "withdrawal:monthly:{$user->id}:" . now()->format('Y-m');
        \Illuminate\Support\Facades\Cache::put($monthlyKey, 4990000, 86400 * 31);

        // Try to withdraw $200 (would exceed $50,000 monthly limit)
        $response = $this->actingAs($user)->postJson('/api/v1/wallet/withdraw', [
            'amount'              => '200.00',
            'destination_address' => '0xABC123',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 'WITHDRAWAL_LIMIT_EXCEEDED');
    }

    // ----------------------------------------------------------------
    // Rate limiting
    // ----------------------------------------------------------------

    public function test_withdrawal_rate_limit_is_enforced(): void
    {
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 10000000]);

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user)->postJson('/api/v1/wallet/withdraw', [
                'amount'              => '1.00',
                'destination_address' => '0xABC123',
            ]);
        }

        $response = $this->actingAs($user)->postJson('/api/v1/wallet/withdraw', [
            'amount'              => '1.00',
            'destination_address' => '0xABC123',
        ]);

        $response->assertStatus(429)
            ->assertJsonPath('code', 'RATE_LIMIT_EXCEEDED');
    }

    // ----------------------------------------------------------------
    // Validation
    // ----------------------------------------------------------------

    public function test_withdrawal_requires_destination_address(): void
    {
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 100000]);

        $response = $this->actingAs($user)->postJson('/api/v1/wallet/withdraw', [
            'amount' => '50.00',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrorFor('destination_address');
    }
}
