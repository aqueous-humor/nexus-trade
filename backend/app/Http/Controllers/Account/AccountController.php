<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Wallet;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * GET /api/v1/accounts
     */
    public function index(Request $request): JsonResponse
    {
        $accounts = $request->user()
            ->accounts()
            ->with(['broker', 'activeSignalSubscription.signal'])
            ->get();

        return response()->json(['data' => $accounts]);
    }

    /**
     * GET /api/v1/accounts/{account}
     */
    public function show(Request $request, Account $account): JsonResponse
    {
        $this->authorizeOwner($request, $account);

        $account->load(['broker', 'activeSignalSubscription.signal']);

        return response()->json(['data' => $account]);
    }

    /**
     * POST /api/v1/accounts
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type'              => ['required', 'in:demo,live'],
            'broker_id'         => ['required_if:type,live', 'nullable', 'exists:brokers,id'],
            'broker_account_id' => ['required_if:type,live', 'nullable', 'string', 'max:100'],
            'leverage'          => ['nullable', 'integer', 'in:1,50,100,200,500,1000'],
        ]);

        // Live accounts require verified email
        if ($data['type'] === 'live' && ! $request->user()->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email verification required to create a live account.',
                'code'    => 'EMAIL_NOT_VERIFIED',
            ], 403);
        }

        // Validate broker is active for live accounts
        if ($data['type'] === 'live') {
            $broker = \App\Models\Broker::findOrFail($data['broker_id']);
            if ($broker->status !== 'active') {
                return response()->json([
                    'message' => 'The selected broker is not currently active.',
                    'code'    => 'BROKER_INACTIVE',
                ], 422);
            }
        }

        $defaultBalance = $data['type'] === 'demo'
            ? (int) config('nexustrade.demo_account_default_balance_cents', 1000000)
            : 0;

        $account = $request->user()->accounts()->create([
            'type'              => $data['type'],
            'broker_id'         => $data['broker_id'] ?? null,
            'broker_account_id' => $data['broker_account_id'] ?? null,
            'leverage'          => $data['leverage'] ?? 100,
            'balance_cents'     => $defaultBalance,
            'status'            => 'active',
        ]);

        return response()->json(['data' => $account->load('broker')], 201);
    }

    /**
     * DELETE /api/v1/accounts/{account}
     */
    public function destroy(Request $request, Account $account): JsonResponse
    {
        $this->authorizeOwner($request, $account);

        $account->delete();

        return response()->json(null, 204);
    }

    /**
     * PATCH /api/v1/accounts/{account}/leverage
     */
    public function updateLeverage(Request $request, Account $account): JsonResponse
    {
        $this->authorizeOwner($request, $account);

        $data = $request->validate([
            'leverage' => ['required', 'integer', 'in:1,50,100,200,500,1000'],
        ]);

        // Block leverage change if there is an active investment on this account
        $hasActiveInvestment = $account->investments()
            ->where('status', 'active')
            ->exists();

        if ($hasActiveInvestment) {
            return response()->json([
                'message' => 'Leverage cannot be changed while an investment is active on this account.',
                'code'    => 'LEVERAGE_CHANGE_BLOCKED',
            ], 422);
        }

        // Validate against broker's permitted range if live account
        if ($account->broker_id) {
            $broker = $account->broker;
            $maxLeverage = $broker->default_leverage;
            if ($data['leverage'] > $maxLeverage) {
                return response()->json([
                    'message'       => "Leverage {$data['leverage']} exceeds broker maximum of {$maxLeverage}.",
                    'code'          => 'LEVERAGE_OUT_OF_RANGE',
                    'permitted_max' => $maxLeverage,
                ], 422);
            }
        }

        $previous = $account->leverage;
        $account->update(['leverage' => $data['leverage']]);

        $this->audit->log(
            operationType: 'PATCH:accounts.leverage',
            actorType: 'user',
            actorId: $request->user()->id,
            targetType: 'account',
            targetId: $account->id,
            outcome: 'success',
            metadata: ['previous_leverage' => $previous, 'new_leverage' => $data['leverage']],
        );

        return response()->json(['data' => $account->fresh()]);
    }

    private function authorizeOwner(Request $request, Account $account): void
    {
        if ($account->user_id !== $request->user()->id) {
            abort(403, 'Forbidden');
        }
    }
}
