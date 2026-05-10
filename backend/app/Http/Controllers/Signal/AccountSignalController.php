<?php

namespace App\Http\Controllers\Signal;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Signal;
use App\Models\SignalSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountSignalController extends Controller
{
    /**
     * POST /api/v1/accounts/{account}/signal
     * Subscribe account to a signal (one active signal per account).
     */
    public function store(Request $request, Account $account): JsonResponse
    {
        $this->authorizeOwner($request, $account);

        $data = $request->validate([
            'signal_id' => ['required', 'integer', 'exists:signals,id'],
        ]);

        $signal = Signal::findOrFail($data['signal_id']);

        if ($signal->status !== 'active') {
            return response()->json([
                'message' => 'Cannot subscribe to an inactive signal.',
                'code'    => 'SIGNAL_INACTIVE',
            ], 422);
        }

        // Unsubscribe from any current active subscription first
        SignalSubscription::where('account_id', $account->id)
            ->whereNull('unsubscribed_at')
            ->update(['unsubscribed_at' => now()]);

        $subscription = SignalSubscription::create([
            'account_id'    => $account->id,
            'signal_id'     => $signal->id,
            'subscribed_at' => now(),
        ]);

        return response()->json(['data' => $subscription->load('signal')], 201);
    }

    /**
     * DELETE /api/v1/accounts/{account}/signal
     * Unsubscribe account from its current signal.
     */
    public function destroy(Request $request, Account $account): JsonResponse
    {
        $this->authorizeOwner($request, $account);

        SignalSubscription::where('account_id', $account->id)
            ->whereNull('unsubscribed_at')
            ->update(['unsubscribed_at' => now()]);

        return response()->json(null, 204);
    }

    private function authorizeOwner(Request $request, Account $account): void
    {
        if ($account->user_id !== $request->user()->id) {
            abort(403, 'Forbidden');
        }
    }
}
