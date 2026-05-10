<?php

namespace App\Services;

use App\Contracts\FraudDetectorInterface;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

class FraudCheckService
{
    public function __construct(private readonly FraudDetectorInterface $detector) {}

    /**
     * Score a transaction and place it in pending_review if score > 80.
     * Returns true if the transaction was flagged.
     */
    public function checkTransaction(Transaction $transaction): bool
    {
        $assessment = $this->detector->scoreTransaction($transaction);

        $autoReviewScore = (int) config('nexustrade.fraud.auto_review_score', 80);

        if ($assessment->score > $autoReviewScore) {
            $transaction->update(['status' => 'pending_review']);
            // Admin notification dispatched in phase 16
            Log::warning("Transaction #{$transaction->id} flagged for review. Score: {$assessment->score}");
            return true;
        }

        return false;
    }
}
