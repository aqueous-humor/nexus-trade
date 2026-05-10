<?php

namespace Tests\Unit;

use App\Models\FeeRule;
use App\Services\FeeCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeeCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private FeeCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = app(FeeCalculator::class);
    }

    public function test_percentage_fee_is_computed_correctly(): void
    {
        FeeRule::create([
            'provider'         => 'Binance',
            'transaction_type' => 'deposit',
            'fee_type'         => 'percentage',
            'fee_value'        => 1.5, // 1.5%
        ]);

        $result = $this->calculator->calculate('Binance', 'deposit', 10_000);

        $this->assertEquals(150, $result['fee_cents']);
        $this->assertEquals(9_850, $result['net_amount_cents']);
    }

    public function test_fixed_fee_is_flat_amount(): void
    {
        FeeRule::create([
            'provider'         => 'KuCoin',
            'transaction_type' => 'withdrawal',
            'fee_type'         => 'fixed',
            'fee_value'        => 5.00, // $5.00 flat
        ]);

        $result = $this->calculator->calculate('KuCoin', 'withdrawal', 50_000);

        $this->assertEquals(500, $result['fee_cents']); // $5.00 = 500 cents
        $this->assertEquals(49_500, $result['net_amount_cents']);
    }

    public function test_zero_fee_when_no_rule_exists(): void
    {
        $result = $this->calculator->calculate('UnknownProvider', 'deposit', 10_000);

        $this->assertEquals(0, $result['fee_cents']);
        $this->assertEquals(10_000, $result['net_amount_cents']);
    }

    public function test_percentage_fee_rounds_correctly(): void
    {
        FeeRule::create([
            'provider'         => 'XT',
            'transaction_type' => 'deposit',
            'fee_type'         => 'percentage',
            'fee_value'        => 1.0, // 1%
        ]);

        // 1% of 333 = 3.33 → rounds to 3
        $result = $this->calculator->calculate('XT', 'deposit', 333);

        $this->assertEquals(3, $result['fee_cents']);
        $this->assertEquals(330, $result['net_amount_cents']);
    }

    public function test_fee_cannot_exceed_gross_amount(): void
    {
        FeeRule::create([
            'provider'         => 'TestProvider',
            'transaction_type' => 'deposit',
            'fee_type'         => 'fixed',
            'fee_value'        => 100.00, // $100 flat fee
        ]);

        // Gross is only $0.50 (50 cents) — fee capped at gross
        $result = $this->calculator->calculate('TestProvider', 'deposit', 50);

        $this->assertEquals(50, $result['fee_cents']);
        $this->assertEquals(0, $result['net_amount_cents']);
    }

    public function test_zero_gross_amount_returns_zero_fee(): void
    {
        FeeRule::create([
            'provider'         => 'Binance',
            'transaction_type' => 'deposit',
            'fee_type'         => 'percentage',
            'fee_value'        => 2.0,
        ]);

        $result = $this->calculator->calculate('Binance', 'deposit', 0);

        $this->assertEquals(0, $result['fee_cents']);
        $this->assertEquals(0, $result['net_amount_cents']);
    }
}
