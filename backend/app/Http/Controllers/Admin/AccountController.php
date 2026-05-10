<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AccountReactivatedMail;
use App\Mail\AccountSuspendedMail;
use App\Models\Account;
use App\Models\Broker;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class AccountController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * GET /api/v1/admin/accounts
     *
     * Paginated list of ALL accounts across all users.
     * Supports filters: user_id, type (demo/live), status (active/suspended/deactivated).
     * Eager-loads user (id, first_name, last_name, email) and broker (id, name).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Account::query()
            ->with([
                'user:id,first_name,last_name,email',
                'broker:id,name',
            ]);

        if ($userId = $request->query('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $accounts = $query->orderByDesc('created_at')->paginate(20);

        return response()->json($accounts);
    }

    /**
     * POST /api/v1/admin/accounts
     *
     * Create an account on behalf of any user.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id'           => ['required', 'integer', 'exists:users,id'],
            'type'              => ['required', 'in:demo,live'],
            'broker_id'         => ['required_if:type,live', 'nullable', 'exists:brokers,id'],
            'broker_account_id' => ['required_if:type,live', 'nullable', 'string', 'max:100'],
            'leverage'          => ['nullable', 'integer', 'in:1,50,100,200,500,1000'],
        ]);

        $balanceCents = $data['type'] === 'demo'
            ? (int) config('nexustrade.demo_account_default_balance_cents', 1_000_000)
            : 0;

        $account = Account::create([
            'user_id'           => $data['user_id'],
            'type'              => $data['type'],
            'broker_id'         => $data['broker_id'] ?? null,
            'broker_account_id' => $data['broker_account_id'] ?? null,
            'leverage'          => $data['leverage'] ?? 100,
            'balance_cents'     => $balanceCents,
            'status'            => 'active',
        ]);

        $this->auditLogger->log(
            operationType: 'admin.account.created',
            actorType: 'admin',
            actorId: $request->user()->id,
            targetType: Account::class,
            targetId: $account->id,
            outcome: 'success',
            metadata: [
                'user_id' => $account->user_id,
                'type'    => $account->type,
            ],
        );

        return response()->json(['data' => $account->load(['user:id,first_name,last_name,email', 'broker:id,name'])], 201);
    }

    /**
     * GET /api/v1/admin/accounts/{account}
     *
     * Full account detail with user, broker, investments count, and signal subscription.
     */
    public function show(Account $account): JsonResponse
    {
        $account->load([
            'user:id,first_name,last_name,email',
            'broker:id,name',
            'activeSignalSubscription.signal',
        ]);
        $account->loadCount('investments');

        return response()->json(['data' => $account]);
    }

    /**
     * PATCH /api/v1/admin/accounts/{account}
     *
     * Update status, leverage, broker_id, broker_account_id.
     */
    public function update(Request $request, Account $account): JsonResponse
    {
        $data = $request->validate([
            'status'            => ['sometimes', 'in:active,suspended,deactivated'],
            'leverage'          => ['sometimes', 'integer', 'in:1,50,100,200,500,1000'],
            'broker_id'         => ['sometimes', 'nullable', 'exists:brokers,id'],
            'broker_account_id' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        $account->update($data);

        $this->auditLogger->log(
            operationType: 'admin.account.updated',
            actorType: 'admin',
            actorId: $request->user()->id,
            targetType: Account::class,
            targetId: $account->id,
            outcome: 'success',
            metadata: ['changes' => array_keys($data)],
        );

        return response()->json(['data' => $account->fresh()->load(['user:id,first_name,last_name,email', 'broker:id,name'])]);
    }

    /**
     * DELETE /api/v1/admin/accounts/{account}
     *
     * Soft-delete the account.
     */
    public function destroy(Request $request, Account $account): JsonResponse
    {
        $account->delete();

        $this->auditLogger->log(
            operationType: 'admin.account.deleted',
            actorType: 'admin',
            actorId: $request->user()->id,
            targetType: Account::class,
            targetId: $account->id,
            outcome: 'success',
            metadata: ['user_id' => $account->user_id],
        );

        return response()->json(null, 204);
    }

    /**
     * PATCH /api/v1/admin/accounts/{account}/status
     *
     * Dedicated endpoint to suspend or reactivate an account.
     * Sends notification email when status changes to suspended or active.
     */
    public function updateStatus(Request $request, Account $account): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:active,suspended,deactivated'],
        ]);

        $previousStatus = $account->status;
        $newStatus = $data['status'];

        $account->update(['status' => $newStatus]);

        $this->auditLogger->log(
            operationType: 'admin.account.status_changed',
            actorType: 'admin',
            actorId: $request->user()->id,
            targetType: Account::class,
            targetId: $account->id,
            outcome: 'success',
            metadata: [
                'previous_status' => $previousStatus,
                'new_status'      => $newStatus,
            ],
        );

        // Send notification email on suspension or reactivation
        $account->load('user');

        if ($newStatus === 'suspended' && $previousStatus !== 'suspended') {
            Mail::to($account->user->email)->queue(new AccountSuspendedMail($account));
        } elseif ($newStatus === 'active' && $previousStatus !== 'active') {
            Mail::to($account->user->email)->queue(new AccountReactivatedMail($account));
        }

        return response()->json(['data' => $account->fresh()->load(['user:id,first_name,last_name,email', 'broker:id,name'])]);
    }

    /**
     * PATCH /api/v1/admin/accounts/{account}/reassign
     *
     * Reassign an account to a different user.
     */
    public function reassign(Request $request, Account $account): JsonResponse
    {
        $data = $request->validate([
            'new_user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $oldUserId = $account->user_id;
        $newUserId = $data['new_user_id'];

        $account->update(['user_id' => $newUserId]);

        $this->auditLogger->log(
            operationType: 'admin.account.reassigned',
            actorType: 'admin',
            actorId: $request->user()->id,
            targetType: Account::class,
            targetId: $account->id,
            outcome: 'success',
            metadata: [
                'old_user_id' => $oldUserId,
                'new_user_id' => $newUserId,
            ],
        );

        return response()->json(['data' => $account->fresh()->load(['user:id,first_name,last_name,email', 'broker:id,name'])]);
    }
}
