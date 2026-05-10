<?php

namespace App\Services;

use App\Contracts\WalletServiceInterface;
use App\Events\WalletUpdated;
use App\Exceptions\InsufficientFundsException;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Values\Money;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class WalletService implements WalletServiceInterface
{
    /**
     * Atomically credit amount to wallet.
     * Uses SELECT FOR UPDATE to prevent race conditions.
     */
    public function credit(int $userId, Money $amount, string $type, array $meta = []): Transaction
    {
        return DB::transaction(function () use ($userId, $amount, $type, $meta): Transaction {
            /** @var Wallet $wallet */
            $wallet = Wallet::where('user_id', $userId)->lockForUpdate()->firstOrFail();

            $wallet->increment('balance_cents', $amount->cents);
            $wallet->refresh();

            $transaction = Transaction::create([
                'user_id'          => $userId,
                'wallet_id'        => $wallet->id,
                'parent_id'        => $meta['parent_id'] ?? null,
                'type'             => $type,
                'status'           => 'completed',
                'amount_cents'     => $amount->cents,
                'fee_cents'        => $meta['fee_cents'] ?? 0,
                'net_amount_cents' => $amount->cents - ($meta['fee_cents'] ?? 0),
                'currency'         => $meta['currency'] ?? 'USD',
                'exchange_rate'    => $meta['exchange_rate'] ?? null,
                'provider'         => $meta['provider'] ?? null,
                'reference'        => $meta['reference'] ?? null,
                'metadata'         => $meta['metadata'] ?? null,
            ]);

            event(new WalletUpdated($wallet));

            return $transaction;
        });
    }

    /**
     * Atomically debit amount from wallet.
     * Throws InsufficientFundsException if balance < amount.
     */
    public function debit(int $userId, Money $amount, string $type, array $meta = []): Transaction
    {
        return DB::transaction(function () use ($userId, $amount, $type, $meta): Transaction {
            /** @var Wallet $wallet */
            $wallet = Wallet::where('user_id', $userId)->lockForUpdate()->firstOrFail();

            if ($wallet->balance_cents < $amount->cents) {
                throw new InsufficientFundsException(
                    "Insufficient funds. Available: {$wallet->balance_cents} cents, required: {$amount->cents} cents."
                );
            }

            $wallet->decrement('balance_cents', $amount->cents);
            $wallet->refresh();

            $transaction = Transaction::create([
                'user_id'          => $userId,
                'wallet_id'        => $wallet->id,
                'parent_id'        => $meta['parent_id'] ?? null,
                'type'             => $type,
                'status'           => 'completed',
                'amount_cents'     => $amount->cents,
                'fee_cents'        => $meta['fee_cents'] ?? 0,
                'net_amount_cents' => $amount->cents - ($meta['fee_cents'] ?? 0),
                'currency'         => $meta['currency'] ?? 'USD',
                'exchange_rate'    => $meta['exchange_rate'] ?? null,
                'provider'         => $meta['provider'] ?? null,
                'destination_address' => $meta['destination_address'] ?? null,
                'reference'        => $meta['reference'] ?? null,
                'metadata'         => $meta['metadata'] ?? null,
            ]);

            event(new WalletUpdated($wallet));

            return $transaction;
        });
    }

    /**
     * Returns current balance as Money value object.
     */
    public function balance(int $userId): Money
    {
        $wallet = Wallet::where('user_id', $userId)->firstOrFail();

        return Money::fromCents($wallet->balance_cents);
    }

    /**
     * Returns paginated transaction history with optional type filter.
     */
    public function history(int $userId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Transaction::where('user_id', $userId)
            ->orderByDesc('created_at');

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($perPage);
    }
}
