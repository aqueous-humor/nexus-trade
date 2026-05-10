<?php

namespace Tests\Feature\Fraud;

use App\Models\FraudAssessment;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\FraudDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FraudDetectorTest extends TestCase
{
    use RefreshDatabase;

    private function makeTransaction(int $userId, int $walletId, string $type, int $amountCents, string $status = 'pending'): Transaction
    {
        return Transaction::create([
            'user_id'          => $userId,
            'wallet_id'        => $walletId,
            'type'             => $type,
            'status'           => $status,
            'amount_cents'     => $amountCents,
            'fee_cents'        => 0,
            'net_amount_cents' => $amountCents,
        ]);
    }

    // ----------------------------------------------------------------
    // Score range
    // ----------------------------------------------------------------

    public function test_score_is_always_between_0_and_100(): void
    {
        $user   = User::factory()->create();
        $wallet = Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);
        $tx     = $this->makeTransaction($user->id, $wallet->id, 'deposit', 100);

        $assessment = app(FraudDetector::class)->scoreTransaction($tx);

        $this->assertGreaterThanOrEqual(0, $assessment->score);
        $this->assertLessThanOrEqual(100, $assessment->score);
    }

    public function test_assessment_is_persisted_to_database(): void
    {
        $user   = User::factory()->create();
        $wallet = Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);
        $tx     = $this->makeTransaction($user->id, $wallet->id, 'deposit', 100);

        app(FraudDetector::class)->scoreTransaction($tx);

        $this->assertDatabaseHas('fraud_assessments', [
            'assessable_type' => 'transaction',
            'assessable_id'   => $tx->id,
        ]);
    }

    // ----------------------------------------------------------------
    // Large transaction rule
    // ----------------------------------------------------------------

    public function test_large_transaction_triggers_score_above_60(): void
    {
        $user   = User::factory()->create();
        $wallet = Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);
        // $15,000 deposit — above $10,000 threshold
        $tx     = $this->makeTransaction($user->id, $wallet->id, 'deposit', 1500000);

        $assessment = app(FraudDetector::class)->scoreTransaction($tx);

        $this->assertGreaterThanOrEqual(60, $assessment->score);
        $this->assertContains('large_transaction', $assessment->triggeredRules);
    }

    // ----------------------------------------------------------------
    // Unusual withdrawal rule
    // ----------------------------------------------------------------

    public function test_unusual_withdrawal_triggers_score_above_75(): void
    {
        $user   = User::factory()->create();
        $wallet = Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);

        // Create a $10,000 deposit in the last 30 days
        $this->makeTransaction($user->id, $wallet->id, 'deposit', 1000000, 'completed');

        // Withdraw $9,000 (90% of deposits — above 80% threshold)
        $tx = $this->makeTransaction($user->id, $wallet->id, 'withdrawal', 900000);

        $assessment = app(FraudDetector::class)->scoreTransaction($tx);

        $this->assertGreaterThanOrEqual(75, $assessment->score);
        $this->assertContains('unusual_withdrawal', $assessment->triggeredRules);
    }

    // ----------------------------------------------------------------
    // Pending review on high score
    // ----------------------------------------------------------------

    public function test_transaction_placed_in_pending_review_when_score_above_80(): void
    {
        $user   = User::factory()->create();
        $wallet = Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);

        // Create 30-day deposit history to trigger unusual_withdrawal (75)
        $this->makeTransaction($user->id, $wallet->id, 'deposit', 1000000, 'completed');

        // Large withdrawal > $10,000 (60) + unusual (75) = capped at 100 > 80
        $tx = $this->makeTransaction($user->id, $wallet->id, 'withdrawal', 1500000);

        $fraudCheck = app(\App\Services\FraudCheckService::class);
        $flagged    = $fraudCheck->checkTransaction($tx);

        $this->assertTrue($flagged);
        $this->assertEquals('pending_review', $tx->fresh()->status);
    }

    // ----------------------------------------------------------------
    // Admin fraud review
    // ----------------------------------------------------------------

    public function test_admin_can_approve_flagged_transaction(): void
    {
        $admin  = User::factory()->admin()->create();
        Wallet::create(['user_id' => $admin->id, 'balance_cents' => 0]);
        $user   = User::factory()->create();
        $wallet = Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);

        $tx = $this->makeTransaction($user->id, $wallet->id, 'deposit', 100, 'pending_review');
        $assessment = FraudAssessment::create([
            'assessable_type' => 'transaction',
            'assessable_id'   => $tx->id,
            'risk_score'      => 85,
            'triggered_rules' => ['large_transaction'],
        ]);

        $response = $this->actingAs($admin)->postJson("/api/v1/admin/fraud/{$assessment->id}/approve");

        $response->assertStatus(200)
            ->assertJsonPath('data.review_decision', 'approved');

        $this->assertEquals('completed', $tx->fresh()->status);
    }

    public function test_admin_can_reject_flagged_transaction(): void
    {
        $admin  = User::factory()->admin()->create();
        Wallet::create(['user_id' => $admin->id, 'balance_cents' => 0]);
        $user   = User::factory()->create();
        $wallet = Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);

        $tx = $this->makeTransaction($user->id, $wallet->id, 'deposit', 100, 'pending_review');
        $assessment = FraudAssessment::create([
            'assessable_type' => 'transaction',
            'assessable_id'   => $tx->id,
            'risk_score'      => 90,
            'triggered_rules' => ['large_transaction', 'unusual_withdrawal'],
        ]);

        $response = $this->actingAs($admin)->postJson("/api/v1/admin/fraud/{$assessment->id}/reject", [
            'reason' => 'Suspicious activity confirmed.',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.review_decision', 'rejected');

        $this->assertEquals('failed', $tx->fresh()->status);
    }

    public function test_admin_can_list_flagged_transactions(): void
    {
        $admin  = User::factory()->admin()->create();
        Wallet::create(['user_id' => $admin->id, 'balance_cents' => 0]);
        $user   = User::factory()->create();
        $wallet = Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);

        $this->makeTransaction($user->id, $wallet->id, 'deposit', 100, 'pending_review');
        $this->makeTransaction($user->id, $wallet->id, 'deposit', 200, 'completed'); // not flagged

        $response = $this->actingAs($admin)->getJson('/api/v1/admin/fraud');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }
}
