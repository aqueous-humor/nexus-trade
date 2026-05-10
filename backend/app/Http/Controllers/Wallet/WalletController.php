<?php

namespace App\Http\Controllers\Wallet;

use App\Contracts\WalletServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(private readonly WalletServiceInterface $walletService) {}

    /**
     * GET /api/v1/wallet
     * Return current balance and 5 most recent transactions.
     */
    public function show(Request $request): JsonResponse
    {
        $userId  = $request->user()->id;
        $balance = $this->walletService->balance($userId);
        $recent  = $this->walletService->history($userId, [], 5);

        return response()->json([
            'data' => [
                'balance_cents' => $balance->cents,
                'balance'       => $balance->toDecimal(),
                'currency'      => 'USD',
                'transactions'  => $recent->items(),
            ],
        ]);
    }

    /**
     * GET /api/v1/wallet/transactions
     * Paginated transaction history with optional type filter.
     */
    public function transactions(Request $request): JsonResponse
    {
        $request->validate([
            'type'     => ['nullable', 'string', 'in:deposit,withdrawal,investment_debit,profit,fee,refund,cancellation'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $userId  = $request->user()->id;
        $filters = array_filter(['type' => $request->type]);
        $perPage = (int) ($request->per_page ?? 20);

        $paginator = $this->walletService->history($userId, $filters, $perPage);

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }
}
