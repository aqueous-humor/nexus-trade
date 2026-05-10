<?php

namespace App\Fraud;

class RuleResult
{
    public function __construct(
        public readonly string $ruleName,
        public readonly int $scoreContribution,
    ) {}
}
