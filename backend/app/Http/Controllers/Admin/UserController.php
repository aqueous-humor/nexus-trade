<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * Paginated list of users with search and filter support.
     *
     * Query params:
     *   - search: matches first_name, last_name, or email (optional)
     *   - role:   filter by role (user|admin) (optional)
     *   - status: filter by status (active|locked) (optional)
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query()
            ->select([
                'id',
                'first_name',
                'last_name',
                'email',
                'phone_number',
                'role',
                'email_verified_at',
                'locked_until',
                'created_at',
            ]);

        // Search by name or email
        if ($search = $request->query('search')) {
            $term = '%' . $search . '%';
            $query->where(function ($q) use ($term): void {
                $q->where('first_name', 'like', $term)
                  ->orWhere('last_name', 'like', $term)
                  ->orWhere('email', 'like', $term);
            });
        }

        // Filter by role
        if ($role = $request->query('role')) {
            $query->where('role', $role);
        }

        // Filter by status
        if ($status = $request->query('status')) {
            if ($status === 'locked') {
                $query->where('locked_until', '>', now());
            } elseif ($status === 'active') {
                $query->where(function ($q): void {
                    $q->whereNull('locked_until')
                      ->orWhere('locked_until', '<=', now());
                });
            }
        }

        $users = $query->orderByDesc('created_at')->paginate(20);

        return response()->json($users);
    }

    /**
     * Return full user detail including related counts and wallet balance.
     */
    public function show(User $user): JsonResponse
    {
        $user->loadCount(['accounts', 'investments']);
        $user->load('wallet');

        return response()->json([
            'data' => [
                'id'                  => $user->id,
                'first_name'          => $user->first_name,
                'last_name'           => $user->last_name,
                'email'               => $user->email,
                'phone_number'        => $user->phone_number,
                'role'                => $user->role,
                'email_verified_at'   => $user->email_verified_at,
                'locked_until'        => $user->locked_until,
                'failed_login_attempts' => $user->failed_login_attempts,
                'created_at'          => $user->created_at,
                'updated_at'          => $user->updated_at,
                'accounts_count'      => $user->accounts_count,
                'investments_count'   => $user->investments_count,
                'wallet_balance_cents' => $user->wallet?->balance_cents ?? 0,
            ],
        ]);
    }

    /**
     * Update a user's role and/or status.
     *
     * Body params:
     *   - role:   user|admin (optional)
     *   - status: active|locked (optional)
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'role'   => ['sometimes', 'in:user,admin'],
            'status' => ['sometimes', 'in:active,locked'],
        ]);

        $changes = [];

        if (isset($data['role'])) {
            $changes['role'] = $data['role'];
        }

        if (isset($data['status'])) {
            if ($data['status'] === 'active') {
                $changes['locked_until']          = null;
                $changes['failed_login_attempts'] = 0;
            } elseif ($data['status'] === 'locked') {
                // Set locked_until far in the future (year 9999)
                $changes['locked_until'] = now()->addYears(100);
            }
        }

        $user->update($changes);

        $this->auditLogger->log(
            operationType: 'admin.user.updated',
            actorType: 'admin',
            actorId: $request->user()->id,
            targetType: User::class,
            targetId: $user->id,
            outcome: 'success',
            metadata: [
                'changes' => array_keys($changes),
                'role'    => $data['role'] ?? null,
                'status'  => $data['status'] ?? null,
            ],
        );

        return response()->json(['data' => $user->fresh()]);
    }
}
