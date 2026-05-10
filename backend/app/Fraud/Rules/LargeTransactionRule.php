<?php

namespace App\Fraud\Rules;

use App\Fraud\FraudContext;
use App\Fraud\FraudRuleInterface;
use App\Fraud\RuleResult;

class LargeTransactionRule implements FraudRuleInterface
{
    public function name(): string
    {
        return 'large_transaction';
    }

    public function evaluate(FraudContext $context): ?RuleResult
    {
        if (! in_array($context->type, ['deposit', 'withdrawal'], true)) {
            return null;
        }

        $threshold = (int) config('nexustrade.fraud.large_transaction_cents', 1000000);

        if ($context->amountCents > $threshold) {
            return new RuleResult($this->name(), 60);
        }

        return null;
    }
}
