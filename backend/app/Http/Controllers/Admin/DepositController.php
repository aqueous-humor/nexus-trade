<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepositController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * GET /api/v1/admin/deposits
     *
     * Lists all deposit transactions with optional status filter and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status'   => ['nullable', 'in:pending,confirmed,rejected'],
            'user_id'  => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        // Map frontend status names to DB values
        $dbStatusMap = ['confirmed' => 'completed', 'rejected' => 'failed', 'pending' => 'pending'];

        $query = Transaction::where('type', 'deposit')
            ->with('user:id,first_name,last_name,email')
            ->orderByDesc('created_at');

        if ($status = $request->query('status')) {
            $dbStatus = $dbStatusMap[$status] ?? $status;
            if ($status === 'pending') {
                $query->whereIn('status', ['pending', 'pending_review']);
            } else {
                $query->where('status', $dbStatus);
            }
        }

        if ($userId = $request->query('user_id')) {
            $query->where('user_id', $userId);
        }

        $deposits = $query->paginate((int) ($request->per_page ?? 20));

        $items = $deposits->getCollection()->map(fn (Transaction $t) => $this->format($t));

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $deposits->currentPage(),
                'last_page'    => $deposits->lastPage(),
                'per_page'     => $deposits->perPage(),
                'total'        => $deposits->total(),
            ],
        ]);
    }

    /**
     * POST /api/v1/admin/deposits/{transaction}/approve
     *
     * Confirms a pending deposit and credits the wallet.
     */
    public function approve(Request $request, Transaction $transaction): JsonResponse
    {
        if ($transaction->type !== 'deposit') {
            return response()->json(['message' => 'Transaction is not a deposit.'], 422);
        }

        if (! in_array($transaction->status, ['pending', 'pending_review'], true)) {
            return response()->json(['message' => 'Deposit cannot be approved in its current state.'], 422);
        }

        // Credit wallet with net amount
        /** @var \App\Contracts\WalletServiceInterface $wallet */
        $wallet = app(\App\Contracts\WalletServiceInterface::class);
        $wallet->credit(
            $transaction->user_id,
            \App\Values\Money::fromCents($transaction->net_amount_cents),
            'deposit',
            ['parent_id' => $transaction->id, 'admin_approved' => true]
        );

        $transaction->update(['status' => 'completed']);

        $this->audit->log(
            operationType: 'admin.deposit.approve',
            actorType: 'admin',
            actorId: $request->user()->id,
            targetType: Transaction::class,
            targetId: $transaction->id,
            outcome: 'success',
        );

        return response()->json(['data' => $this->format($transaction->fresh())]);
    }

    /**
     * POST /api/v1/admin/deposits/{transaction}/reject
     */
    public function reject(Request $request, Transaction $transaction): JsonResponse
    {
        if ($transaction->type !== 'deposit') {
            return response()->json(['message' => 'Transaction is not a deposit.'], 422);
        }

        if (! in_array($transaction->status, ['pending', 'pending_review'], true)) {
            return response()->json(['message' => 'Deposit cannot be rejected in its current state.'], 422);
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $transaction->update([
            'status'   => 'failed',
            'metadata' => array_merge($transaction->metadata ?? [], ['reject_reason' => $data['reason']]),
        ]);

        $this->audit->log(
            operationType: 'admin.deposit.reject',
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
        $statusMap = ['completed' => 'confirmed', 'failed' => 'rejected', 'pending_review' => 'pending'];

        return [
            'id'             => $t->id,
            'user'           => $t->user ? [
                'id'    => $t->user->id,
                'email' => $t->user->email,
            ] : null,
            'amount_cents'   => $t->amount_cents,
            'currency'       => $t->currency,
            'status'         => $statusMap[$t->status] ?? $t->status,
            'payment_method' => $t->provider ?? 'default',
            'reference'      => $t->reference ?? (string) $t->id,
            'wallet_address' => $t->metadata['wallet_address'] ?? null,
            'created_at'     => $t->created_at?->toIso8601String(),
        ];
    }
}
