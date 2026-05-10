<?php

namespace Tests\Property;

use App\Exceptions\InsufficientFundsException;
use App\Models\User;
use App\Models\Wallet;
use App\Services\WalletService;
use App\Values\Money;
use Eris\Generator;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-based tests for the WalletService.
 *
 * Feature: forex-broker-platform
 */
class WalletPropertyTest extends TestCase
{
    use RefreshDatabase;
    use TestTrait;

    private WalletService $walletService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->walletService = app(WalletService::class);
    }

    /**
     * Feature: forex-broker-platform, Property 7: Deposit credit is atomic and exact.
     *
     * For any confirmed deposit with gross amount G (in USD cents) and fee F,
     * the User's wallet balance SHALL increase by exactly G − F.
     */
    public function test_p7_deposit_credit_increases_balance_by_net_amount(): void
    {
        $this->forAll(
            Generator\choose(100, 1000000), // gross amount in cents ($1–$10,000)
            Generator\choose(0, 99)         // fee as percentage of gross (0–99%)
        )
        ->then(function (int $gross, int $feePct) {
            $fee = (int) floor($gross * $feePct / 100);
            $net = $gross - $fee;

            $user = User::factory()->create();
            Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);

            $before = $this->walletService->balance($user->id)->cents;

            $this->walletService->credit(
                $user->id,
                Money::fromCents($net),
                'deposit',
                ['fee_cents' => $fee]
            );

            $after = $this->walletService->balance($user->id)->cents;

            $this->assertEquals(
                $before + $net,
                $after,
                "Expected balance to increase by net amount {$net} cents"
            );
        });
    }

    /**
     * Feature: forex-broker-platform, Property 8: Wallet debit never produces a negative balance.
     *
     * For any wallet with balance B and any debit of amount D where D > B,
     * the operation SHALL be rejected and the balance SHALL remain exactly B.
     */
    public function test_p8_wallet_debit_never_produces_negative_balance(): void
    {
        $this->forAll(
            Generator\choose(1, 100000),  // balance in cents ($0.01–$1000)
            Generator\choose(1, 200000)   // debit amount in cents
        )
        ->when(fn ($balance, $debit) => $debit > $balance) // debit exceeds balance
        ->then(function (int $balance, int $debit) {
            $user   = User::factory()->create();
            Wallet::create(['user_id' => $user->id, 'balance_cents' => $balance]);

            $exceptionThrown = false;

            try {
                $this->walletService->debit($user->id, Money::fromCents($debit), 'withdrawal');
            } catch (InsufficientFundsException) {
                $exceptionThrown = true;
            }

            $this->assertTrue($exceptionThrown, 'InsufficientFundsException should have been thrown');

            // Balance must remain unchanged
            $this->assertEquals(
                $balance,
                $this->walletService->balance($user->id)->cents,
                "Balance should remain {$balance} after failed debit"
            );
        });
    }

    /**
     * Sanity: credit followed by debit of same amount returns to original balance.
     */
    public function test_credit_then_debit_returns_to_original_balance(): void
    {
        $this->forAll(
            Generator\choose(100, 100000) // amount in cents
        )
        ->then(function (int $amount) {
            $user = User::factory()->create();
            Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);

            $this->walletService->credit($user->id, Money::fromCents($amount), 'deposit');
            $this->walletService->debit($user->id, Money::fromCents($amount), 'withdrawal');

            $this->assertEquals(0, $this->walletService->balance($user->id)->cents);
        });
    }
}
