<?php

namespace Tests\Unit;

use App\Services\InvestmentService;
use Tests\TestCase;

class ProfitCalculatorTest extends TestCase
{
    public function test_win_profit_equals_round_amount_times_roi_over_100(): void
    {
        $this->assertEquals(1000, InvestmentService::calculateProfit(10_000, 10.0, 'WIN'));
    }

    public function test_win_profit_rounds_correctly(): void
    {
        // 10000 * 1.5 / 100 = 150.0 → 150
        $this->assertEquals(150, InvestmentService::calculateProfit(10_000, 1.5, 'WIN'));

        // 333 * 10 / 100 = 33.3 → 33
        $this->assertEquals(33, InvestmentService::calculateProfit(333, 10.0, 'WIN'));

        // 1 * 50 / 100 = 0.5 → 1 (rounds up)
        $this->assertEquals(1, InvestmentService::calculateProfit(1, 50.0, 'WIN'));
    }

    public function test_loss_profit_is_zero(): void
    {
        $this->assertEquals(0, InvestmentService::calculateProfit(10_000, 10.0, 'LOSS'));
    }

    public function test_draw_profit_is_zero(): void
    {
        $this->assertEquals(0, InvestmentService::calculateProfit(10_000, 10.0, 'DRAW'));
    }

    public function test_win_with_zero_roi_returns_zero(): void
    {
        $this->assertEquals(0, InvestmentService::calculateProfit(10_000, 0.0, 'WIN'));
    }

    public function test_win_with_100_percent_roi(): void
    {
        $this->assertEquals(10_000, InvestmentService::calculateProfit(10_000, 100.0, 'WIN'));
    }

    public function test_win_with_large_amount(): void
    {
        // 1,000,000 cents * 12.5% = 125,000 cents
        $this->assertEquals(125_000, InvestmentService::calculateProfit(1_000_000, 12.5, 'WIN'));
    }
}
