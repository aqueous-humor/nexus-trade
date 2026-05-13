<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WithdrawalController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * GET /api/v1/admin/withdrawals
     *
     * Lists all withdrawal transactions with optional status filter and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status'   => ['nullable', 'in:pending,approved,rejected'],
            'user_id'  => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        // Map frontend status names to DB values
        $dbStatusMap = ['approved' => 'completed', 'rejected' => 'failed', 'pending' => 'pending'];

        $query = Transaction::where('type', 'withdrawal')
            ->with('user:id,first_name,last_name,email')
            ->orderByDesc('created_at');

        if ($status = $request->query('status')) {
            if ($status === 'pending') {
                $query->whereIn('status', ['pending', 'pending_review']);
            } else {
                $query->where('status', $dbStatusMap[$status] ?? $status);
            }
        }

        if ($userId = $request->query('user_id')) {
            $query->where('user_id', $userId);
        }

        $withdrawals = $query->paginate((int) ($request->per_page ?? 20));

        $items = $withdrawals->getCollection()->map(fn (Transaction $t) => $this->format($t));

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $withdrawals->currentPage(),
                'last_page'    => $withdrawals->lastPage(),
                'per_page'     => $withdrawals->perPage(),
                'total'        => $withdrawals->total(),
            ],
        ]);
    }

    /**
     * POST /api/v1/admin/withdrawals/{transaction}/approve
     *
     * Marks a pending withdrawal as completed (funds already debited on request).
     */
    public function approve(Request $request, Transaction $transaction): JsonResponse
    {
        if ($transaction->type !== 'withdrawal') {
            return response()->json(['message' => 'Transaction is not a withdrawal.'], 422);
        }

        if (! in_array($transaction->status, ['pending', 'pending_review'], true)) {
            return response()->json(['message' => 'Withdrawal cannot be approved in its current state.'], 422);
        }

        $transaction->update(['status' => 'completed']);

        $this->audit->log(
            operationType: 'admin.withdrawal.approve',
            actorType: 'admin',
            actorId: $request->user()->id,
            targetType: Transaction::class,
            targetId: $transaction->id,
            outcome: 'success',
        );

        return response()->json(['data' => $this->format($transaction->fresh())]);
    }

    /**
     * POST /api/v1/admin/withdrawals/{transaction}/reject
     *
     * Rejects a pending withdrawal and refunds the debited amount back to the wallet.
     */
    public function reject(Request $request, Transaction $transaction): JsonResponse
    {
        if ($transaction->type !== 'withdrawal') {
            return response()->json(['message' => 'Transaction is not a withdrawal.'], 422);
        }

        if (! in_array($transaction->status, ['pending', 'pending_review'], true)) {
            return response()->json(['message' => 'Withdrawal cannot be rejected in its current state.'], 422);
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        // Refund — credit the gross amount back since wallet was debited on submission
        /** @var \App\Contracts\WalletServiceInterface $wallet */
        $wallet = app(\App\Contracts\WalletServiceInterface::class);
        $wallet->credit(
            $transaction->user_id,
            \App\Values\Money::fromCents($transaction->amount_cents),
            'withdrawal_refund',
            ['parent_id' => $transaction->id, 'admin_rejected' => true, 'reason' => $data['reason']]
        );

        $transaction->update([
            'status'   => 'failed',
            'metadata' => array_merge($transaction->metadata ?? [], ['reject_reason' => $data['reason']]),
        ]);

        $this->audit->log(
            operationType: 'admin.withdrawal.reject',
            actorType: 'admin',
            actorId: $request->user()->id,
            targetType: Transaction::class,
            targetId: $transaction->id,
            outcome: 'success',
            metadata: ['reason' => $data['reason']],
        );

        return response()->json(['data' => $this->format($transaction->fresh())]);
    }

    private function format(Transaction $t): array
    {
        // Normalize DB status to frontend-friendly names
        $statusMap = ['completed' => 'approved', 'failed' => 'rejected', 'pending_review' => 'pending'];

        return [
            'id'             => $t->id,
            'user'           => $t->user ? [
                'id'    => $t->user->id,
                'email' => $t->user->email,
            ] : null,
            'amount_cents'   => $t->amount_cents,
            'currency'       => $t->currency ?? 'USD',
            'status'         => $statusMap[$t->status] ?? $t->status,
            'wallet_address' => $t->destination_address ?? ($t->metadata['destination_address'] ?? null),
            'created_at'     => $t->created_at?->toIso8601String(),
        ];
    }
}
