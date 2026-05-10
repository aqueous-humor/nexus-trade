<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Duration;
use App\Models\InvestmentPlan;
use App\Models\TermsAcceptance;
use App\Models\TermsVersion;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Rate limiting feature tests — Req 14
 * Deposit (5/min) and Withdrawal (5/min) limits are tested in
 * DepositTest and WithdrawalTest respectively.
 * This file covers the investment rate limit (10/hour).
 */
class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
    }

    public function test_investment_rate_limit_triggers_at_10_per_hour(): void
    {
        $user     = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);
        $account  = Account::factory()->create(['user_id' => $user->id, 'balance_cents' => 100000000]);
        $plan     = InvestmentPlan::factory()->create(['min_amount_cents' => 100, 'max_amount_cents' => 10000000, 'roi_percentage' => 10]);
        $duration = Duration::factory()->daily()->create();
        $terms    = TermsVersion::factory()->create();
        TermsAcceptance::create(['user_id' => $user->id, 'terms_version' => $terms->version, 'accepted_at' => now(), 'ip_address' => '127.0.0.1']);

        // Make 10 investments (the limit)
        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($user)->postJson('/api/v1/investments', [
                'account_id'     => $account->id,
                'plan_id'        => $plan->id,
                'duration_id'    => $duration->id,
                'amount'         => '1.00',
                'terms_accepted' => true,
            ]);
        }

        // 11th should be rate limited
        $response = $this->actingAs($user)->postJson('/api/v1/investments', [
            'account_id'     => $account->id,
            'plan_id'        => $plan->id,
            'duration_id'    => $duration->id,
            'amount'         => '1.00',
            'terms_accepted' => true,
        ]);

        $response->assertStatus(429)
            ->assertJsonPath('code', 'RATE_LIMIT_EXCEEDED')
            ->assertHeader('Retry-After');
    }

    public function test_deposit_rate_limit_returns_retry_after_header(): void
    {
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user)->postJson('/api/v1/wallet/deposit', ['currency' => 'USD', 'amount' => '1.00']);
        }

        $response = $this->actingAs($user)->postJson('/api/v1/wallet/deposit', ['currency' => 'USD', 'amount' => '1.00']);

        $response->assertStatus(429)
            ->assertHeader('Retry-After');
    }

    public function test_withdrawal_rate_limit_returns_retry_after_header(): void
    {
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 10000000]);

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user)->postJson('/api/v1/wallet/withdraw', ['amount' => '1.00', 'destination_address' => '0xABC']);
        }

        $response = $this->actingAs($user)->postJson('/api/v1/wallet/withdraw', ['amount' => '1.00', 'destination_address' => '0xABC']);

        $response->assertStatus(429)
            ->assertHeader('Retry-After');
    }
}
