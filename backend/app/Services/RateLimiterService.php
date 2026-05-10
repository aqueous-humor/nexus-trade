<?php

namespace App\Services;

use App\Contracts\RateLimiterServiceInterface;
use App\Exceptions\RateLimitExceededException;
use Illuminate\Support\Facades\RateLimiter;

class RateLimiterService implements RateLimiterServiceInterface
{
    public function attempt(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        return RateLimiter::attempt($key, $maxAttempts, fn () => true, $decaySeconds);
    }

    public function availableIn(string $key): int
    {
        return RateLimiter::availableIn($key);
    }

    /** 5 deposits per minute */
    public function checkDeposit(int $userId): void
    {
        $key = "deposit:{$userId}";
        if (! $this->attempt($key, 5, 60)) {
            throw new RateLimitExceededException($this->availableIn($key));
        }
    }

    /** 5 withdrawals per minute */
    public function checkWithdrawal(int $userId): void
    {
        $key = "withdrawal:{$userId}";
        if (! $this->attempt($key, 5, 60)) {
            throw new RateLimitExceededException($this->availableIn($key));
        }
    }

    /** 10 investments per hour */
    public function checkInvestment(int $userId): void
    {
        $key = "investment:{$userId}";
        if (! $this->attempt($key, 10, 3600)) {
            throw new RateLimitExceededException($this->availableIn($key));
        }
    }
}
