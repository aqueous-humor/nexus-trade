<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\InvestmentServiceInterface;
use App\DTOs\CreateInvestmentDTO;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Investment;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InvestmentController extends Controller
{
    public function __construct(
        private readonly InvestmentServiceInterface $investmentService,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * GET /api/v1/admin/investments
     *
     * Paginated list of ALL investments across all users.
     * Filters: user_id, account_id, plan_id, status, result, date_from, date_to.
     * Eager-loads: user (id, first_name, last_name, email), account (id, type),
     *              plan (id, name), duration (id, label).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Investment::query()
            ->with([
                'user:id,first_name,last_name,email',
                'account:id,type',
                'plan:id,name',
                'duration:id,label',
            ])
            ->orderByDesc('created_at');

        if ($userId = $request->query('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($accountId = $request->query('account_id')) {
            $query->where('account_id', $accountId);
        }

        if ($planId = $request->query('plan_id')) {
            $query->where('plan_id', $planId);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($result = $request->query('result')) {
            $query->where('result', $result);
        }

        if ($dateFrom = $request->query('date_from')) {
            $query->where('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->query('date_to')) {
            $query->where('created_at', '<=', $dateTo);
        }

        $investments = $query->paginate(20);

        return response()->json($investments);
    }

    /**
     * POST /api/v1/admin/investments
     *
     * Manually create an investment on behalf of a user.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id'     => ['required', 'integer', 'exists:users,id'],
            'account_id'  => ['required', 'integer', 'exists:accounts,id'],
            'plan_id'     => ['required', 'integer', 'exists:investment_plans,id'],
            'duration_id' => ['required', 'integer', 'exists:durations,id'],
            'amount_cents' => ['required', 'integer', 'min:1'],
        ]);

        // Verify account belongs to the specified user
        $account = Account::findOrFail($data['account_id']);
        if ((int) $account->user_id !== (int) $data['user_id']) {
            return response()->json([
                'message' => 'The selected account does not belong to the specified user.',
                'errors'  => ['account_id' => ['Account does not belong to the specified user.']],
            ], 422);
        }

        // Verify duration is linked to the plan
        $durationLinked = DB::table('plan_durations')
            ->where('plan_id', $data['plan_id'])
            ->where('duration_id', $data['duration_id'])
            ->exists();

        if (! $durationLinked) {
            return response()->json([
                'message' => 'The selected duration is not available for the specified plan.',
                'errors'  => ['duration_id' => ['Duration is not linked to the specified plan.']],
            ], 422);
        }

        $user = User::findOrFail($data['user_id']);

        $dto = new CreateInvestmentDTO(
            planId: $data['plan_id'],
            durationId: $data['duration_id'],
            amountCents: $data['amount_cents'],
            termsVersion: 'admin',
        );

        // Compute maturity_at from the duration
        $duration = \App\Models\Duration::findOrFail($data['duration_id']);
        $maturityAt = match ($duration->unit) {
            'hour'  => now()->addHours($duration->value),
            'day'   => now()->addDays($duration->value),
            'week'  => now()->addWeeks($duration->value),
            'month' => now()->addMonths($duration->value),
            default => now()->addDays($duration->value),
        };

        // Create investment directly to bypass user-facing restrictions (terms, rate limits)
        $investment = Investment::create([
            'user_id'          => $user->id,
            'account_id'       => $account->id,
            'plan_id'          => $dto->planId,
            'duration_id'      => $dto->durationId,
            'amount_cents'     => $dto->amountCents,
            'profit_cents'     => 0,
            'status'           => 'pending',
            'maturity_at'      => $maturityAt,
            'terms_version'    => $dto->termsVersion,
            'created_by_admin' => true,
        ]);

        $this->auditLogger->log(
            operationType: 'admin.investment.created',
            actorType: 'admin',
            actorId: $request->user()->id,
            targetType: Investment::class,
            targetId: $investment->id,
            outcome: 'success',
            metadata: [
                'user_id'      => $user->id,
                'account_id'   => $account->id,
                'plan_id'      => $dto->planId,
                'duration_id'  => $dto->durationId,
                'amount_cents' => $dto->amountCents,
            ],
        );

        return response()->json([
            'data' => $investment->load([
                'user:id,first_name,last_name,email',
                'account:id,type',
                'plan:id,name',
                'duration:id,label',
            ]),
        ], 201);
    }

    /**
     * GET /api/v1/admin/investments/{investment}
     *
     * Full investment detail with all relations.
     */
    public function show(Investment $investment): JsonResponse
    {
        $investment->load([
            'user:id,first_name,last_name,email',
            'account:id,type',
            'plan',
            'duration',
        ]);

        return response()->json(['data' => $investment]);
    }

    /**
     * PATCH /api/v1/admin/investments/{investment}/status
     *
     * Update investment status to any valid state.
     * Uses InvestmentService methods where possible.
     */
    public function updateStatus(Request $request, Investment $investment): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'active', 'completed', 'cancelled', 'rejected'])],
            'reason' => ['nullable', 'string'],
            'result' => ['nullable', Rule::in(['WIN', 'LOSS', 'DRAW'])],
        ]);

        $previousStatus = $investment->status;
        $newStatus = $data['status'];

        try {
            $updated = match ($newStatus) {
                'active'    => $this->investmentService->activate($investment),
                'completed' => $this->investmentService->complete($investment, $data['result'] ?? 'DRAW'),
                'cancelled' => $this->investmentService->cancel($investment),
                'rejected'  => $this->investmentService->reject($investment, $data['reason'] ?? 'Admin action'),
                default     => tap($investment)->update(['status' => $newStatus]),
            };
        } catch (\App\Exceptions\InvalidStateTransitionException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        $this->auditLogger->log(
            operationType: 'admin.investment.status_updated',
            actorType: 'admin',
            actorId: $request->user()->id,
            targetType: Investment::class,
            targetId: $investment->id,
            outcome: 'success',
            metadata: [
                'previous_status' => $previousStatus,
                'new_status'      => $newStatus,
                'reason'          => $data['reason'] ?? null,
            ],
        );

        return response()->json(['data' => $updated->load([
            'user:id,first_name,last_name,email',
            'account:id,type',
            'plan:id,name',
            'duration:id,label',
        ])]);
    }

    /**
     * PATCH /api/v1/admin/investments/{investment}/result
     *
     * Record result (WIN/LOSS/DRAW) and profit_cents for a completed investment.
     */
    public function recordResult(Request $request, Investment $investment): JsonResponse
    {
        $data = $request->validate([
            'result'       => ['required', Rule::in(['WIN', 'LOSS', 'DRAW'])],
            'profit_cents' => ['required', 'integer', 'min:0'],
        ]);

        $investment->update([
            'result'       => $data['result'],
            'profit_cents' => $data['profit_cents'],
        ]);

        $this->auditLogger->log(
            operationType: 'admin.investment.result_recorded',
            actorType: 'admin',
            actorId: $request->user()->id,
            targetType: Investment::class,
            targetId: $investment->id,
            outcome: 'success',
            metadata: [
                'result'       => $data['result'],
                'profit_cents' => $data['profit_cents'],
            ],
        );

        return response()->json(['data' => $investment->fresh()->load([
            'user:id,first_name,last_name,email',
            'account:id,type',
            'plan:id,name',
            'duration:id,label',
        ])]);
    }

    /**
     * POST /api/v1/admin/investments/{investment}/recover
     *
     * Trigger a recovery operation on a stuck investment.
     * Logs the attempt and outcome to the audit log.
     */
    public function recover(Request $request, Investment $investment): JsonResponse
    {
        try {
            $updated = $this->investmentService->recover($investment);
        } catch (\Throwable $e) {
            $this->auditLogger->log(
                operationType: 'admin.investment.recovered',
                actorType: 'admin',
                actorId: $request->user()->id,
                targetType: Investment::class,
                targetId: $investment->id,
                outcome: 'error',
                metadata: [
                    'investment_id' => $investment->id,
                    'error'         => $e->getMessage(),
                ],
            );

            return response()->json(['message' => $e->getMessage()], 422);
        }

        $this->auditLogger->log(
            operationType: 'admin.investment.recovered',
            actorType: 'admin',
            actorId: $request->user()->id,
            targetType: Investment::class,
            targetId: $investment->id,
            outcome: 'success',
            metadata: [
                'investment_id' => $investment->id,
            ],
        );

        return response()->json(['data' => $updated->load([
            'user:id,first_name,last_name,email',
            'account:id,type',
            'plan:id,name',
            'duration:id,label',
        ])]);
    }

    /**
     * PATCH /api/v1/admin/investments/{investment}/profit
     *
     * Adjust profit amount with a reason.
     * Logs original_profit_cents, adjusted_profit_cents, admin_id, and reason to audit_log.
     */
    public function adjustProfit(Request $request, Investment $investment): JsonResponse
    {
        $data = $request->validate([
            'adjusted_profit_cents' => ['required', 'integer', 'min:0'],
            'reason'                => ['required', 'string'],
        ]);

        $originalProfitCents = $investment->profit_cents;

        $investment->update([
            'adjusted_profit_cents' => $data['adjusted_profit_cents'],
        ]);

        $this->auditLogger->log(
            operationType: 'admin.investment.profit_adjusted',
            actorType: 'admin',
            actorId: $request->user()->id,
            targetType: Investment::class,
            targetId: $investment->id,
            outcome: 'success',
            metadata: [
                'original_profit_cents'  => $originalProfitCents,
                'adjusted_profit_cents'  => $data['adjusted_profit_cents'],
                'admin_id'               => $request->user()->id,
                'reason'                 => $data['reason'],
            ],
        );

        return response()->json(['data' => $investment->fresh()->load([
            'user:id,first_name,last_name,email',
            'account:id,type',
            'plan:id,name',
            'duration:id,label',
        ])]);
    }
}
