<?php

namespace App\Contracts;

use App\Models\Transaction;
use App\Values\Money;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface WalletServiceInterface
{
    /**
     * Atomically credit amount to wallet.
     */
    public function credit(int $userId, Money $amount, string $type, array $meta = []): Transaction;

    /**
     * Atomically debit amount from wallet.
     * Throws InsufficientFundsException if balance < amount.
     */
    public function debit(int $userId, Money $amount, string $type, array $meta = []): Transaction;

    /**
     * Returns current balance as Money value object.
     */
    public function balance(int $userId): Money;

    /**
     * Returns paginated transaction history.
     */
    public function history(int $userId, array $filters = [], int $perPage = 20): LengthAwarePaginator;
}
