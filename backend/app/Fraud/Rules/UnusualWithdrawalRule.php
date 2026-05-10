<?php

namespace App\Fraud\Rules;

use App\Fraud\FraudContext;
use App\Fraud\FraudRuleInterface;
use App\Fraud\RuleResult;
use App\Models\Transaction;

class UnusualWithdrawalRule implements FraudRuleInterface
{
    public function name(): string
    {
        return 'unusual_withdrawal';
    }

    public function evaluate(FraudContext $context): ?RuleResult
    {
        if ($context->type !== 'withdrawal') {
            return null;
        }

        $ratio = (float) config('nexustrade.fraud.unusual_withdrawal_ratio', 0.80);

        $thirtyDayDeposits = Transaction::where('user_id', $context->userId)
            ->where('type', 'deposit')
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('amount_cents');

        if ($thirtyDayDeposits > 0 && $context->amountCents > ($thirtyDayDeposits * $ratio)) {
            return new RuleResult($this->name(), 75);
        }

        return null;
    }
}
