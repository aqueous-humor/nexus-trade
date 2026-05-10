<?php

namespace App\DTOs;

class RiskAssessment
{
    /**
     * @param  int      $score         Risk score from 0 to 100
     * @param  string[] $triggeredRules Names of rules that were triggered
     */
    public function __construct(
        public readonly int $score,
        public readonly array $triggeredRules,
    ) {}
}
