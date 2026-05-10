<?php

namespace Tests\Property;

use App\Models\FeeRule;
use App\Services\FeeCalculator;
use Eris\Generator;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: forex-broker-platform, Property 13: Percentage fee computation is exact.
 */
class FeePropertyTest extends TestCase
{
    use RefreshDatabase;
    use TestTrait;

    /**
     * P13: For any transaction with amount A and percentage fee rate P,
     * fee = round(A × P / 100) and net = A - fee.
     */
    public function test_p13_percentage_fee_computation_is_exact(): void
    {
        $calculator = app(FeeCalculator::class);

        $this->forAll(
            Generator\choose(100, 10000000),  // amount in cents ($1–$100,000)
            Generator\choose(1, 500)          // rate in hundredths of percent (0.01%–5%)
        )
        ->then(function (int $grossCents, int $rateTimes100) use ($calculator) {
            $rate = $rateTimes100 / 100; // e.g. 150 → 1.50%

            FeeRule::firstOrCreate(
                ['provider' => 'test_provider', 'transaction_type' => 'deposit'],
                ['fee_type' => 'percentage', 'fee_value' => $rate]
            )->update(['fee_value' => $rate]);

            $result = $calculator->calculate('test_provider', 'deposit', $grossCents);

            $expectedFee = (int) round($grossCents * $rate / 100);
            $expectedNet = $grossCents - $expectedFee;

            $this->assertEquals($expectedFee, $result['fee_cents'],
                "Fee should be round({$grossCents} × {$rate} / 100) = {$expectedFee}"
            );
            $this->assertEquals($expectedNet, $result['net_amount_cents'],
                "Net should be {$grossCents} - {$expectedFee} = {$expectedNet}"
            );
        });
    }

    /**
     * Fixed fee: always the flat amount regardless of transaction size.
     */
    public function test_fixed_fee_is_flat_amount(): void
    {
        $calculator = app(FeeCalculator::class);

        $this->forAll(
            Generator\choose(10000, 10000000), // amount $100–$100,000
            Generator\choose(100, 5000)        // fixed fee $1–$50
        )
        ->then(function (int $grossCents, int $fixedFeeCents) use ($calculator) {
            $fixedFeeUsd = $fixedFeeCents / 100;

            FeeRule::firstOrCreate(
                ['provider' => 'fixed_provider', 'transaction_type' => 'deposit'],
                ['fee_type' => 'fixed', 'fee_value' => $fixedFeeUsd]
            )->update(['fee_value' => $fixedFeeUsd]);

            $result = $calculator->calculate('fixed_provider', 'deposit', $grossCents);

            $expectedFee = (int) round($fixedFeeUsd * 100);

            $this->assertEquals($expectedFee, $result['fee_cents']);
            $this->assertEquals($grossCents - $expectedFee, $result['net_amount_cents']);
        });
    }
}
