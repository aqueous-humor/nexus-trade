<?php

namespace Tests\Unit;

use App\Exceptions\InsufficientFundsException;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\WalletService;
use App\Values\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class WalletServiceTest extends TestCase
{
    use RefreshDatabase;

    private WalletService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        $this->service = app(WalletService::class);
        $this->user    = User::factory()->create();
        Wallet::create(['user_id' => $this->user->id, 'balance_cents' => 0]);
    }

    // ── credit() ─────────────────────────────────────────────────────────────

    public function test_credit_increases_balance_by_exact_amount(): void
    {
        $this->service->credit($this->user->id, Money::fromCents(5000), 'deposit');

        $this->assertEquals(5000, $this->service->balance($this->user->id)->cents);
    }

    public function test_credit_creates_transaction_record(): void
    {
        $tx = $this->service->credit($this->user->id, Money::fromCents(10000), 'deposit', [
            'fee_cents' => 100,
            'currency'  => 'USD',
        ]);

        $this->assertInstanceOf(Transaction::class, $tx);
        $this->assertEquals('deposit', $tx->type);
        $this->assertEquals('completed', $tx->status);
        $this->assertEquals(10000, $tx->amount_cents);
        $this->assertEquals(100, $tx->fee_cents);
        $this->assertEquals(9900, $tx->net_amount_cents);
        $this->assertEquals('USD', $tx->currency);
    }

    public function test_credit_multiple_times_accumulates_balance(): void
    {
        $this->service->credit($this->user->id, Money::fromCents(1000), 'deposit');
        $this->service->credit($this->user->id, Money::fromCents(2000), 'deposit');
        $this->service->credit($this->user->id, Money::fromCents(3000), 'deposit');

        $this->assertEquals(6000, $this->service->balance($this->user->id)->cents);
    }

    public function test_credit_with_zero_fee_sets_net_equal_to_amount(): void
    {
        $tx = $this->service->credit($this->user->id, Money::fromCents(5000), 'deposit');

        $this->assertEquals(0, $tx->fee_cents);
        $this->assertEquals(5000, $tx->net_amount_cents);
    }

    // ── debit() ──────────────────────────────────────────────────────────────

    public function test_debit_decreases_balance_by_exact_amount(): void
    {
        Wallet::where('user_id', $this->user->id)->update(['balance_cents' => 10000]);

        $this->service->debit($this->user->id, Money::fromCents(3000), 'withdrawal');

        $this->assertEquals(7000, $this->service->balance($this->user->id)->cents);
    }

    public function test_debit_creates_transaction_record(): void
    {
        Wallet::where('user_id', $this->user->id)->update(['balance_cents' => 10000]);

        $tx = $this->service->debit($this->user->id, Money::fromCents(5000), 'withdrawal', [
            'destination_address' => '0xABC',
        ]);

        $this->assertEquals('withdrawal', $tx->type);
        $this->assertEquals('completed', $tx->status);
        $this->assertEquals(5000, $tx->amount_cents);
        $this->assertEquals('0xABC', $tx->destination_address);
    }

    public function test_debit_throws_insufficient_funds_when_balance_too_low(): void
    {
        Wallet::where('user_id', $this->user->id)->update(['balance_cents' => 100]);

        $this->expectException(InsufficientFundsException::class);

        $this->service->debit($this->user->id, Money::fromCents(200), 'withdrawal');
    }

    public function test_debit_does_not_change_balance_on_insufficient_funds(): void
    {
        Wallet::where('user_id', $this->user->id)->update(['balance_cents' => 100]);

        try {
            $this->service->debit($this->user->id, Money::fromCents(200), 'withdrawal');
        } catch (InsufficientFundsException) {
            // expected
        }

        $this->assertEquals(100, $this->service->balance($this->user->id)->cents);
    }

    public function test_debit_exact_balance_succeeds(): void
    {
        Wallet::where('user_id', $this->user->id)->update(['balance_cents' => 5000]);

        $this->service->debit($this->user->id, Money::fromCents(5000), 'withdrawal');

        $this->assertEquals(0, $this->service->balance($this->user->id)->cents);
    }

    // ── balance() ────────────────────────────────────────────────────────────

    public function test_balance_returns_money_value_object(): void
    {
        Wallet::where('user_id', $this->user->id)->update(['balance_cents' => 12345]);

        $balance = $this->service->balance($this->user->id);

        $this->assertInstanceOf(Money::class, $balance);
        $this->assertEquals(12345, $balance->cents);
    }

    public function test_balance_returns_zero_for_empty_wallet(): void
    {
        $this->assertEquals(0, $this->service->balance($this->user->id)->cents);
    }

    // ── history() ────────────────────────────────────────────────────────────

    public function test_history_returns_paginated_transactions(): void
    {
        Wallet::where('user_id', $this->user->id)->update(['balance_cents' => 50000]);

        $this->service->credit($this->user->id, Money::fromCents(1000), 'deposit');
        $this->service->credit($this->user->id, Money::fromCents(2000), 'deposit');

        $history = $this->service->history($this->user->id);

        $this->assertEquals(2, $history->total());
    }

    public function test_history_filters_by_type(): void
    {
        Wallet::where('user_id', $this->user->id)->update(['balance_cents' => 50000]);

        $this->service->credit($this->user->id, Money::fromCents(1000), 'deposit');
        $this->service->debit($this->user->id, Money::fromCents(500), 'withdrawal');

        $deposits = $this->service->history($this->user->id, ['type' => 'deposit']);
        $this->assertEquals(1, $deposits->total());

        $withdrawals = $this->service->history($this->user->id, ['type' => 'withdrawal']);
        $this->assertEquals(1, $withdrawals->total());
    }

    public function test_history_returns_empty_for_new_user(): void
    {
        $history = $this->service->history($this->user->id);

        $this->assertEquals(0, $history->total());
    }
}
