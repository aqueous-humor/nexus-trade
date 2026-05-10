<?php

namespace App\Services;

use App\Models\FeeRule;

class FeeCalculator
{
    /**
     * Compute fee for a given provider, transaction type, and gross amount.
     *
     * @return array{fee_cents: int, net_amount_cents: int}
     */
    public function calculate(string $provider, string $transactionType, int $grossCents): array
    {
        $rule = FeeRule::where('provider', $provider)
            ->where('transaction_type', $transactionType)
            ->first();

        if (! $rule) {
            return ['fee_cents' => 0, 'net_amount_cents' => $grossCents];
        }

        $feeCents = match ($rule->fee_type) {
            'fixed'      => (int) round((float) $rule->fee_value * 100),
            'percentage' => (int) round($grossCents * (float) $rule->fee_value / 100),
            default      => 0,
        };

        // Fee cannot exceed gross amount
        $feeCents = min($feeCents, $grossCents);

        return [
            'fee_cents'        => $feeCents,
            'net_amount_cents' => $grossCents - $feeCents,
        ];
    }
}
