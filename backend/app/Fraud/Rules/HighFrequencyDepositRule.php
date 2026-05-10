<?php

namespace App\Fraud\Rules;

use App\Fraud\FraudContext;
use App\Fraud\FraudRuleInterface;
use App\Fraud\RuleResult;
use Illuminate\Support\Facades\Cache;

class HighFrequencyDepositRule implements FraudRuleInterface
{
    public function name(): string
    {
        return 'high_frequency_deposit';
    }

    public function evaluate(FraudContext $context): ?RuleResult
    {
        if ($context->type !== 'deposit') {
            return null;
        }

        $threshold  = (int) config('nexustrade.fraud.high_frequency_threshold', 3);
        $windowMins = (int) config('nexustrade.fraud.high_frequency_window_minutes', 10);
        $key        = "fraud:deposits:{$context->userId}";

        // Get current count in window
        $count = (int) Cache::get($key, 0);

        // Record this deposit attempt
        Cache::put($key, $count + 1, $windowMins * 60);

        // Trigger if this is the Nth deposit (>= threshold)
        if ($count + 1 >= $threshold) {
            return new RuleResult($this->name(), 70);
        }

        return null;
    }
}
