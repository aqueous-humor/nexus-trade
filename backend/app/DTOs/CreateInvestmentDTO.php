<?php

namespace App\DTOs;

class CreateInvestmentDTO
{
    public function __construct(
        public readonly int $planId,
        public readonly int $durationId,
        public readonly int $amountCents,
        public readonly string $termsVersion,
    ) {}
}
