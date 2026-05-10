<?php

namespace App\Contracts;

interface RateLimiterServiceInterface
{
    /**
     * Returns true if the operation is allowed; false if limit exceeded.
     */
    public function attempt(string $key, int $maxAttempts, int $decaySeconds): bool;

    /**
     * Returns seconds until the key resets.
     */
    public function availableIn(string $key): int;
}
