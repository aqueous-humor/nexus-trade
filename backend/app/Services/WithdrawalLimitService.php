<?php

namespace App\Services;

use App\Exceptions\WithdrawalLimitExceededException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class WithdrawalLimitService
{
    private int $dailyLimitCents;
    private int $monthlyLimitCents;

    public function __construct()
    {
        $this->dailyLimitCents   = (int) config('nexustrade.withdrawal_daily_limit_cents', 500000);
        $this->monthlyLimitCents = (int) config('nexustrade.withdrawal_monthly_limit_cents', 5000000);
    }

    /**
     * Check limits and throw if exceeded.
     * Does NOT increment — call increment() after the debit succeeds.
     */
    public function check(int $userId, int $amountCents): void
    {
        $daily   = $this->getDailyTotal($userId);
        $monthly = $this->getMonthlyTotal($userId);

        if ($daily + $amountCents > $this->dailyLimitCents) {
            $remaining = max(0, $this->dailyLimitCents - $daily);
            throw new WithdrawalLimitExceededException(
                remainingCents: $remaining,
                resetsAt: Carbon::tomorrow()->startOfDay(),
                message: "Daily withdrawal limit exceeded. Remaining: \${$this->centsToUsd($remaining)}."
            );
        }

        if ($monthly + $amountCents > $this->monthlyLimitCents) {
            $remaining = max(0, $this->monthlyLimitCents - $monthly);
            throw new WithdrawalLimitExceededException(
                remainingCents: $remaining,
                resetsAt: Carbon::now()->startOfMonth()->addMonth(),
                message: "Monthly withdrawal limit exceeded. Remaining: \${$this->centsToUsd($remaining)}."
            );
        }
    }

    /**
     * Increment both daily and monthly totals after a successful withdrawal.
     */
    public function increment(int $userId, int $amountCents): void
    {
        $dailyKey   = $this->dailyKey($userId);
        $monthlyKey = $this->monthlyKey($userId);

        // Atomic increment with TTL
        $dailyTtl   = (int) Carbon::tomorrow()->startOfDay()->diffInSeconds(now());
        $monthlyTtl = (int) Carbon::now()->startOfMonth()->addMonth()->diffInSeconds(now());

        $this->atomicIncrement($dailyKey, $amountCents, $dailyTtl);
        $this->atomicIncrement($monthlyKey, $amountCents, $monthlyTtl);
    }

    public function getDailyTotal(int $userId): int
    {
        return (int) Cache::get($this->dailyKey($userId), 0);
    }

    public function getMonthlyTotal(int $userId): int
    {
        return (int) Cache::get($this->monthlyKey($userId), 0);
    }

    public function getDailyRemaining(int $userId): int
    {
        return max(0, $this->dailyLimitCents - $this->getDailyTotal($userId));
    }

    public function getMonthlyRemaining(int $userId): int
    {
        return max(0, $this->monthlyLimitCents - $this->getMonthlyTotal($userId));
    }

    private function dailyKey(int $userId): string
    {
        return "withdrawal:daily:{$userId}:" . now()->format('Y-m-d');
    }

    private function monthlyKey(int $userId): string
    {
        return "withdrawal:monthly:{$userId}:" . now()->format('Y-m');
    }

    private function atomicIncrement(string $key, int $amount, int $ttlSeconds): void
    {
        $current = (int) Cache::get($key, 0);
        Cache::put($key, $current + $amount, $ttlSeconds);
    }

    private function centsToUsd(int $cents): string
    {
        return number_format($cents / 100, 2);
    }
}
