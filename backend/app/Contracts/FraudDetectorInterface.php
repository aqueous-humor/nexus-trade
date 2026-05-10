<?php

namespace App\Contracts;

use App\DTOs\RiskAssessment;
use App\Models\Investment;
use App\Models\Transaction;

interface FraudDetectorInterface
{
    /**
     * Scores a transaction; returns RiskAssessment (score 0-100, triggered rules).
     */
    public function scoreTransaction(Transaction $transaction): RiskAssessment;

    /**
     * Scores an investment at creation time.
     */
    public function scoreInvestment(Investment $investment): RiskAssessment;
}
