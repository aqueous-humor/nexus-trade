<?php

namespace App\Fraud\Rules;

use App\Fraud\FraudContext;
use App\Fraud\FraudRuleInterface;
use App\Fraud\RuleResult;
use App\Models\User;

class NewAccountLargeDepositRule implements FraudRuleInterface
{
    public function name(): string
    {
        return 'new_account_large_deposit';
    }

    public function evaluate(FraudContext $context): ?RuleResult
    {
        if ($context->type !== 'deposit') {
            return null;
        }

        $threshold = 500000; // $5,000

        if ($context->amountCents <= $threshold) {
            return null;
        }

        $user = User::find($context->userId);
        if ($user && $user->created_at->diffInDays(now()) < 7) {
            return new RuleResult($this->name(), 50);
        }

        return null;
    }
}
