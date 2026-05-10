<?php

namespace Tests\Property;

use App\Exceptions\InvalidStateTransitionException;
use App\Models\Account;
use App\Models\Duration;
use App\Models\Investment;
use App\Models\InvestmentPlan;
use App\Models\TermsAcceptance;
use App\Models\TermsVersion;
use App\Models\User;
use App\Models\Wallet;
use App\Services\InvestmentService;
use App\DTOs\CreateInvestmentDTO;
use Eris\Generator;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Feature: forex-broker-platform
 */
class InvestmentPropertyTest extends TestCase
{
    use RefreshDatabase;
    use TestTrait;

    private InvestmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake(); // prevent broadcast errors in tests
        $this->service = app(InvestmentService::class);
    }

    /**
     * P3: Investment creation atomically deducts account balance.
     * After creation with amount A against account with balance B >= A,
     * account balance SHALL be exactly B - A.
     */
    public function test_p3_investment_creation_deducts_account_balance(): void
    {
        $this->forAll(
            Generator\choose(10000, 100000),  // balance $100–$1000
            Generator\choose(1000, 9999)      // amount $10–$99.99 (always < balance)
        )
        ->then(function (int $balance, int $amount) {
            $user     = User::factory()->create();
            Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);
            $account  = Account::factory()->create(['user_id' => $user->id, 'balance_cents' => $balance]);
            $plan     = InvestmentPlan::factory()->create([
                'min_amount_cents' => 100,
                'max_amount_cents' => 1000000,
                'roi_percentage'   => 10,
            ]);
            $duration = Duration::firstOrCreate(
                ['unit' => 'day', 'value' => 1],
                ['label' => '1 Day']
            );
            $terms    = TermsVersion::firstOrCreate(
                ['version' => 'v1.0'],
                ['content' => 'Test terms', 'effective_at' => now()->subDay()]
            );
            TermsAcceptance::firstOrCreate(
                ['user_id' => $user->id, 'terms_version' => $terms->version],
                ['accepted_at' => now(), 'ip_address' => '127.0.0.1']
            );

            $dto = new CreateInvestmentDTO($plan->id, $duration->id, $amount, $terms->version);
            $this->service->create($user, $account, $dto);

            $this->assertEquals(
                $balance - $amount,
                $account->fresh()->balance_cents,
                "Balance should be B-A after investment creation"
            );
        });
    }

    /**
     * P4: Investment state machine rejects invalid transitions.
     * Attempting an invalid transition SHALL be rejected and status unchanged.
     */
    public function test_p4_state_machine_rejects_invalid_transitions(): void
    {
        $invalidTransitions = [
            ['pending',   'completed'],
            ['cancelled', 'active'],
            ['rejected',  'active'],
            ['completed', 'active'],
            ['completed', 'cancelled'],
        ];

        $user     = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);
        $account  = Account::factory()->create(['user_id' => $user->id]);
        $plan     = InvestmentPlan::factory()->create();
        $duration = Duration::firstOrCreate(['unit' => 'day', 'value' => 2], ['label' => '2 Days']);

        foreach ($invalidTransitions as [$from, $to]) {
            $investment = Investment::factory()->create([
                'user_id'    => $user->id,
                'account_id' => $account->id,
                'plan_id'    => $plan->id,
                'duration_id'=> $duration->id,
                'status'     => $from,
            ]);

            $exceptionThrown = false;
            try {
                match ($to) {
                    'active'    => $this->service->activate($investment),
                    'completed' => $this->service->complete($investment, 'WIN'),
                    'cancelled' => $this->service->cancel($investment),
                    'rejected'  => $this->service->reject($investment, 'test'),
                };
            } catch (InvalidStateTransitionException) {
                $exceptionThrown = true;
            }

            $this->assertTrue($exceptionThrown,
                "Transition {$from}→{$to} should throw InvalidStateTransitionException"
            );
            $this->assertEquals($from, $investment->fresh()->status,
                "Status should remain '{$from}' after invalid transition attempt"
            );
        }
    }

    /**
     * P5: Investment rejection atomically refunds account balance.
     * After rejection, account balance SHALL increase by exactly A.
     */
    public function test_p5_rejection_refunds_account_balance(): void
    {
        $this->forAll(
            Generator\choose(10000, 500000)  // investment amount $100–$5000
        )
        ->then(function (int $amount) {
            $user       = User::factory()->create();
            Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);
            $account    = Account::factory()->create(['user_id' => $user->id, 'balance_cents' => 0]);
            $plan       = InvestmentPlan::factory()->create();
            $duration   = Duration::firstOrCreate(
                ['unit' => 'day', 'value' => 1],
                ['label' => '1 Day']
            );
            $investment = Investment::factory()->create([
                'user_id'      => $user->id,
                'account_id'   => $account->id,
                'plan_id'      => $plan->id,
                'duration_id'  => $duration->id,
                'amount_cents' => $amount,
                'status'       => 'pending',
            ]);

            $balanceBefore = $account->fresh()->balance_cents;
            $this->service->reject($investment, 'test rejection');

            $this->assertEquals(
                $balanceBefore + $amount,
                $account->fresh()->balance_cents,
                "Account balance should increase by exactly {$amount} after rejection"
            );
        });
    }

    /**
     * P6: WIN profit calculation is exact.
     * profit_cents SHALL equal round(amount_cents × roi_percentage / 100).
     */
    public function test_p6_win_profit_calculation_is_exact(): void
    {
        $this->minimumEvaluationRatio(0.5);

        $this->forAll(
            Generator\choose(10000, 1000000),  // amount $100–$10000
            Generator\choose(1, 500)           // ROI 0.01%–5%
        )
        ->then(function (int $amount, int $roiTimes100) {
            $roi      = $roiTimes100 / 100; // e.g. 150 → 1.50%
            $expected = (int) round($amount * $roi / 100);
            $actual   = InvestmentService::calculateProfit($amount, $roi, 'WIN');

            $this->assertEquals($expected, $actual,
                "WIN profit for amount={$amount}, roi={$roi}% should be {$expected}"
            );
        });
    }
}
