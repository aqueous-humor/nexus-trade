<?php

namespace App\Http\Controllers\Wallet;

use App\Contracts\WalletServiceInterface;
use App\Http\Controllers\Controller;
use App\Services\FeeCalculator;
use App\Services\RateLimiterService;
use App\Services\WithdrawalLimitService;
use App\Values\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WithdrawalController extends Controller
{
    public function __construct(
        private readonly WalletServiceInterface $walletService,
        private readonly FeeCalculator $feeCalculator,
        private readonly RateLimiterService $rateLimiter,
        private readonly WithdrawalLimitService $limitService,
    ) {}

    /**
     * POST /api/v1/wallet/withdraw
     */
    public function store(Request $request): JsonResponse
    {
        // Rate limit: 5 withdrawals per minute
        $this->rateLimiter->checkWithdrawal($request->user()->id);

        $data = $request->validate([
            'amount'              => ['required', 'numeric', 'min:0.01'],
            'destination_address' => ['required', 'string', 'max:255'],
            'provider'            => ['nullable', 'string', 'max:100'],
        ]);

        $userId      = $request->user()->id;
        $provider    = $data['provider'] ?? 'default';
        $grossCents  = (int) round((float) $data['amount'] * 100);

        // Check daily/monthly limits
        $this->limitService->check($userId, $grossCents);

        // Compute fee
        $fees = $this->feeCalculator->calculate($provider, 'withdrawal', $grossCents);

        // Debit wallet (throws InsufficientFundsException if balance too low)
        $transaction = $this->walletService->debit(
            $userId,
            Money::fromCents($grossCents),
            'withdrawal',
            [
                'fee_cents'           => $fees['fee_cents'],
                'provider'            => $provider,
                'destination_address' => $data['destination_address'],
            ]
        );

        // Increment limit counters after successful debit
        $this->limitService->increment($userId, $grossCents);

        return response()->json([
            'data' => [
                'transaction_id'      => $transaction->id,
                'amount'              => number_format($grossCents / 100, 2),
                'fee'                 => number_format($fees['fee_cents'] / 100, 2),
                'net_amount'          => number_format($fees['net_amount_cents'] / 100, 2),
                'destination_address' => $data['destination_address'],
                'status'              => $transaction->status,
                'daily_remaining'     => number_format($this->limitService->getDailyRemaining($userId) / 100, 2),
                'monthly_remaining'   => number_format($this->limitService->getMonthlyRemaining($userId) / 100, 2),
            ],
        ], 201);
    }
}
