<?php

namespace Tests\Property;

use App\Fraud\FraudContext;
use App\Fraud\Rules\HighFrequencyDepositRule;
use App\Fraud\Rules\LargeTransactionRule;
use App\Fraud\Rules\UnusualWithdrawalRule;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\FraudDetector;
use Eris\Generator;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: forex-broker-platform — Fraud Detection Properties P9–P12
 */
class FraudPropertyTest extends TestCase
{
    use RefreshDatabase;
    use TestTrait;

    /**
     * P9: Fraud score is always in range [0, 100].
     */
    public function test_p9_fraud_score_always_in_range_0_to_100(): void
    {
        $this->forAll(
            Generator\choose(1, 10000000),  // amount in cents
            Generator\elements('deposit', 'withdrawal', 'investment')
        )
        ->then(function (int $amount, string $type) {
            $user = User::factory()->create();
            Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);

            $tx = Transaction::create([
                'user_id'          => $user->id,
                'wallet_id'        => $user->wallet->id,
                'type'             => $type === 'investment' ? 'deposit' : $type,
                'status'           => 'pending',
                'amount_cents'     => $amount,
                'fee_cents'        => 0,
                'net_amount_cents' => $amount,
            ]);

            $detector    = app(FraudDetector::class);
            $assessment  = $detector->scoreTransaction($tx);

            $this->assertGreaterThanOrEqual(0, $assessment->score);
            $this->assertLessThanOrEqual(100, $assessment->score);
        });
    }

    /**
     * P10: High-frequency deposit rule fires at threshold (>= 3 deposits in 10 min).
     */
    public function test_p10_high_frequency_rule_fires_at_threshold(): void
    {
        $rule = new HighFrequencyDepositRule();

        $this->forAll(
            Generator\choose(3, 10)  // number of deposits >= threshold
        )
        ->then(function (int $depositCount) use ($rule) {
            $userId = rand(200000, 299999);

            // Simulate N-1 previous deposits by calling evaluate N-1 times
            for ($i = 0; $i < $depositCount - 1; $i++) {
                $rule->evaluate(new FraudContext($userId, 10000, 'deposit'));
            }

            // The Nth deposit should trigger the rule
            $result = $rule->evaluate(new FraudContext($userId, 10000, 'deposit'));

            $this->assertNotNull($result, "Rule should fire after {$depositCount} deposits");
            $this->assertGreaterThanOrEqual(70, $result->scoreContribution);
            $this->assertEquals('high_frequency_deposit', $result->ruleName);
        });
    }

    /**
     * P11: Large transaction rule fires above $10,000 threshold.
     */
    public function test_p11_large_transaction_rule_fires_above_threshold(): void
    {
        $rule = new LargeTransactionRule();

        $this->forAll(
            Generator\choose(1000001, 10000000)  // > $10,000 in cents
        )
        ->then(function (int $amount) use ($rule) {
            $result = $rule->evaluate(new FraudContext(1, $amount, 'deposit'));

            $this->assertNotNull($result, "Rule should fire for amount {$amount} > 1,000,000 cents");
            $this->assertGreaterThanOrEqual(60, $result->scoreContribution);
            $this->assertEquals('large_transaction', $result->ruleName);
        });
    }

    /**
     * P12: Unusual withdrawal rule fires when withdrawal > 80% of 30-day deposits.
     */
    public function test_p12_unusual_withdrawal_rule_fires_above_ratio(): void
    {
        $this->forAll(
            Generator\choose(10000, 500000)  // deposit total in cents
        )
        ->then(function (int $depositTotal) {
            $user = User::factory()->create();
            Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);

            // Create a completed deposit in the last 30 days
            Transaction::create([
                'user_id'          => $user->id,
                'wallet_id'        => $user->wallet->id,
                'type'             => 'deposit',
                'status'           => 'completed',
                'amount_cents'     => $depositTotal,
                'fee_cents'        => 0,
                'net_amount_cents' => $depositTotal,
                'created_at'       => now()->subDays(5),
            ]);

            // Withdrawal > 80% of deposits
            $withdrawalAmount = (int) ($depositTotal * 0.85);
            $rule             = new UnusualWithdrawalRule();
            $result           = $rule->evaluate(new FraudContext($user->id, $withdrawalAmount, 'withdrawal'));

            $this->assertNotNull($result, "Rule should fire for withdrawal {$withdrawalAmount} > 80% of {$depositTotal}");
            $this->assertGreaterThanOrEqual(75, $result->scoreContribution);
            $this->assertEquals('unusual_withdrawal', $result->ruleName);
        });
    }
}
