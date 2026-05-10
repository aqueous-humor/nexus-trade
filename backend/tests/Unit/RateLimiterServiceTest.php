<?php

namespace Tests\Unit;

use App\Exceptions\RateLimitExceededException;
use App\Services\RateLimiterService;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RateLimiterServiceTest extends TestCase
{
    private RateLimiterService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(RateLimiterService::class);
        RateLimiter::clear('test-key');
    }

    public function test_attempt_returns_true_when_under_limit(): void
    {
        $result = $this->service->attempt('test-key', 5, 60);

        $this->assertTrue($result);
    }

    public function test_attempt_returns_false_when_limit_exceeded(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->service->attempt('test-key', 5, 60);
        }

        $result = $this->service->attempt('test-key', 5, 60);

        $this->assertFalse($result);
    }

    public function test_available_in_returns_positive_seconds_when_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->service->attempt('test-key', 5, 60);
        }

        $availableIn = $this->service->availableIn('test-key');

        $this->assertGreaterThan(0, $availableIn);
        $this->assertLessThanOrEqual(60, $availableIn);
    }

    public function test_available_in_returns_zero_when_not_limited(): void
    {
        $availableIn = $this->service->availableIn('test-key');

        $this->assertEquals(0, $availableIn);
    }

    public function test_check_deposit_throws_after_5_attempts(): void
    {
        $userId = 42;
        RateLimiter::clear("deposit:{$userId}");

        for ($i = 0; $i < 5; $i++) {
            $this->service->checkDeposit($userId);
        }

        $this->expectException(RateLimitExceededException::class);
        $this->service->checkDeposit($userId);
    }

    public function test_check_withdrawal_throws_after_5_attempts(): void
    {
        $userId = 43;
        RateLimiter::clear("withdrawal:{$userId}");

        for ($i = 0; $i < 5; $i++) {
            $this->service->checkWithdrawal($userId);
        }

        $this->expectException(RateLimitExceededException::class);
        $this->service->checkWithdrawal($userId);
    }

    public function test_check_investment_throws_after_10_attempts(): void
    {
        $userId = 44;
        RateLimiter::clear("investment:{$userId}");

        for ($i = 0; $i < 10; $i++) {
            $this->service->checkInvestment($userId);
        }

        $this->expectException(RateLimitExceededException::class);
        $this->service->checkInvestment($userId);
    }

    public function test_different_keys_are_independent(): void
    {
        RateLimiter::clear('key-a');
        RateLimiter::clear('key-b');

        for ($i = 0; $i < 5; $i++) {
            $this->service->attempt('key-a', 5, 60);
        }

        // key-b should still be allowed
        $this->assertTrue($this->service->attempt('key-b', 5, 60));
    }
}
