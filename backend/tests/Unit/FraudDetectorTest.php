<?php

namespace Tests\Unit;

use App\Fraud\FraudContext;
use App\Fraud\Rules\HighFrequencyDepositRule;
use App\Fraud\Rules\LargeTransactionRule;
use App\Fraud\Rules\NewAccountLargeDepositRule;
use App\Fraud\Rules\RapidSuccessionWithdrawalRule;
use App\Fraud\Rules\UnusualWithdrawalRule;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\FraudDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class FraudDetectorTest extends TestCase
{
    use RefreshDatabase;

    // ── LargeTransactionRule ─────────────────────────────────────────────────

    public function test_large_transaction_rule_fires_above_threshold(): void
    {
        $rule    = new LargeTransactionRule();
        $context = new FraudContext(userId: 1, type: 'deposit', amountCents: 1_000_001);

        $result = $rule->evaluate($context);

        $this->assertNotNull($result);
        $this->assertEquals('large_transaction', $result->ruleName);
        $this->assertEquals(60, $result->scoreContribution);
    }

    public function test_large_transaction_rule_does_not_fire_at_threshold(): void
    {
        $rule    = new LargeTransactionRule();
        $context = new FraudContext(userId: 1, type: 'deposit', amountCents: 1_000_000);

        $result = $rule->evaluate($context);

        $this->assertNull($result);
    }

    public function test_large_transaction_rule_ignores_non_financial_types(): void
    {
        $rule    = new LargeTransactionRule();
        $context = new FraudContext(userId: 1, type: 'profit', amountCents: 9_999_999);

        $result = $rule->evaluate($context);

        $this->assertNull($result);
    }

    // ── HighFrequencyDepositRule ─────────────────────────────────────────────

    public function test_high_frequency_rule_fires_at_threshold(): void
    {
        Cache::flush();
        $rule = new HighFrequencyDepositRule();

        // First two deposits — should not fire
        $context = new FraudContext(userId: 99, type: 'deposit', amountCents: 100);
        $this->assertNull($rule->evaluate($context)); // count = 1
        $this->assertNull($rule->evaluate($context)); // count = 2

        // Third deposit — should fire (threshold = 3)
        $result = $rule->evaluate($context); // count = 3
        $this->assertNotNull($result);
        $this->assertEquals('high_frequency_deposit', $result->ruleName);
        $this->assertEquals(70, $result->scoreContribution);
    }

    public function test_high_frequency_rule_ignores_non_deposits(): void
    {
        Cache::flush();
        $rule    = new HighFrequencyDepositRule();
        $context = new FraudContext(userId: 99, type: 'withdrawal', amountCents: 100);

        $this->assertNull($rule->evaluate($context));
        $this->assertNull($rule->evaluate($context));
        $this->assertNull($rule->evaluate($context));
    }

    // ── UnusualWithdrawalRule ────────────────────────────────────────────────

    public function test_unusual_withdrawal_rule_fires_above_ratio(): void
    {
        $user   = User::factory()->create();
        $wallet = Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);

        // Seed 30-day deposits totalling $1,000
        Transaction::create([
            'user_id'          => $user->id,
            'wallet_id'        => $wallet->id,
            'type'             => 'deposit',
            'status'           => 'completed',
            'amount_cents'     => 100_000,
            'fee_cents'        => 0,
            'net_amount_cents' => 100_000,
            'currency'         => 'USD',
            'created_at'       => now()->subDays(5),
        ]);

        $rule    = new UnusualWithdrawalRule();
        // Withdrawal > 80% of $1,000 = $800
        $context = new FraudContext(userId: $user->id, type: 'withdrawal', amountCents: 85_000);

        $result = $rule->evaluate($context);

        $this->assertNotNull($result);
        $this->assertEquals('unusual_withdrawal', $result->ruleName);
        $this->assertEquals(75, $result->scoreContribution);
    }

    public function test_unusual_withdrawal_rule_does_not_fire_below_ratio(): void
    {
        $user   = User::factory()->create();
        $wallet = Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);

        Transaction::create([
            'user_id'          => $user->id,
            'wallet_id'        => $wallet->id,
            'type'             => 'deposit',
            'status'           => 'completed',
            'amount_cents'     => 100_000,
            'fee_cents'        => 0,
            'net_amount_cents' => 100_000,
            'currency'         => 'USD',
        ]);

        $rule    = new UnusualWithdrawalRule();
        $context = new FraudContext(userId: $user->id, type: 'withdrawal', amountCents: 50_000); // 50%

        $this->assertNull($rule->evaluate($context));
    }

    // ── Score aggregation & cap ──────────────────────────────────────────────

    public function test_score_is_capped_at_100(): void
    {
        Cache::flush();
        $user   = User::factory()->create();
        $wallet = Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);

        // Seed deposits to trigger unusual withdrawal rule
        Transaction::create([
            'user_id'          => $user->id,
            'wallet_id'        => $wallet->id,
            'type'             => 'deposit',
            'status'           => 'completed',
            'amount_cents'     => 100_000,
            'fee_cents'        => 0,
            'net_amount_cents' => 100_000,
            'currency'         => 'USD',
        ]);

        // Trigger high-frequency rule (3 deposits in cache)
        $cacheKey = "fraud:deposits:{$user->id}";
        Cache::put($cacheKey, 2, 600); // pre-seed 2 so next deposit triggers

        $tx = Transaction::create([
            'user_id'          => $user->id,
            'wallet_id'        => $wallet->id,
            'type'             => 'deposit',
            'status'           => 'completed',
            'amount_cents'     => 2_000_000, // > $10k → large_transaction (60) + high_frequency (70) = 130 → capped at 100
            'fee_cents'        => 0,
            'net_amount_cents' => 2_000_000,
            'currency'         => 'USD',
        ]);

        $detector    = app(FraudDetector::class);
        $assessment  = $detector->scoreTransaction($tx);

        $this->assertLessThanOrEqual(100, $assessment->score);
        $this->assertGreaterThan(0, $assessment->score);
    }

    public function test_score_is_zero_for_small_normal_transaction(): void
    {
        Cache::flush();
        $user   = User::factory()->create();
        $wallet = Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);

        $tx = Transaction::create([
            'user_id'          => $user->id,
            'wallet_id'        => $wallet->id,
            'type'             => 'deposit',
            'status'           => 'completed',
            'amount_cents'     => 1_000, // $10 — no rules fire
            'fee_cents'        => 0,
            'net_amount_cents' => 1_000,
            'currency'         => 'USD',
        ]);

        $detector   = app(FraudDetector::class);
        $assessment = $detector->scoreTransaction($tx);

        $this->assertEquals(0, $assessment->score);
        $this->assertEmpty($assessment->triggeredRules);
    }
}
