<?php

namespace App\Fraud\Rules;

use App\Fraud\FraudContext;
use App\Fraud\FraudRuleInterface;
use App\Fraud\RuleResult;
use App\Models\Transaction;

class RapidSuccessionWithdrawalRule implements FraudRuleInterface
{
    public function name(): string
    {
        return 'rapid_succession_withdrawal';
    }

    public function evaluate(FraudContext $context): ?RuleResult
    {
        if ($context->type !== 'withdrawal') {
            return null;
        }

        $recentCount = Transaction::where('user_id', $context->userId)
            ->where('type', 'withdrawal')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->count();

        if ($recentCount >= 2) {
            return new RuleResult($this->name(), 55);
        }

        return null;
    }
}
