<?php

namespace App\Fraud;

interface FraudRuleInterface
{
    public function evaluate(FraudContext $context): ?RuleResult;
    public function name(): string;
}
