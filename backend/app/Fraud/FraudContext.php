<?php

namespace App\Fraud;

use App\Models\Investment;
use App\Models\Transaction;

class FraudContext
{
    public function __construct(
        public readonly int $userId,
        public readonly int $amountCents,
        public readonly string $type,          // 'deposit' | 'withdrawal' | 'investment'
        public readonly ?int $entityId = null, // Transaction or Investment ID
    ) {}

    public static function fromTransaction(Transaction $tx): self
    {
        return new self(
            userId:    $tx->user_id,
            amountCents: $tx->amount_cents,
            type:      $tx->type,
            entityId:  $tx->id,
        );
    }

    public static function fromInvestment(Investment $inv): self
    {
        return new self(
            userId:    $inv->user_id,
            amountCents: $inv->amount_cents,
            type:      'investment',
            entityId:  $inv->id,
        );
    }
}
