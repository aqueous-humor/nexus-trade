<?php

namespace App\Http\Controllers\Investment;

use App\Contracts\InvestmentServiceInterface;
use App\DTOs\CreateInvestmentDTO;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Investment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvestmentController extends Controller
{
    public function __construct(private readonly InvestmentServiceInterface $investmentService) {}

    /**
     * GET /api/v1/investments
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'status'   => ['nullable', 'in:pending,active,completed,cancelled,rejected'],
        ]);

        $perPage = (int) ($request->per_page ?? 20);

        $query = $request->user()
            ->investments()
            ->with(['plan', 'duration', 'account'])
            ->orderByDesc('created_at');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $paginator = $query->paginate($perPage);

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

    /**
     * POST /api/v1/investments
     */
    public function store(Request $request): JsonResponse
    {
        // Rate limit: 10 investments per hour
        app(\App\Services\RateLimiterService::class)->checkInvestment($request->user()->id);

        $data = $request->validate([
            'account_id'     => ['required', 'integer', 'exists:accounts,id'],
            'plan_id'        => ['required', 'integer', 'exists:investment_plans,id'],
            'duration_id'    => ['required', 'integer', 'exists:durations,id'],
            'amount'         => ['required', 'numeric', 'min:0.01'],
            'terms_accepted' => ['required', 'accepted'],
        ]);

        $account = Account::findOrFail($data['account_id']);

        // Ownership check
        if ($account->user_id !== $request->user()->id) {
            abort(403, 'Forbidden');
        }

        $amountCents = (int) round((float) $data['amount'] * 100);

        $dto = new CreateInvestmentDTO(
            planId: $data['plan_id'],
            durationId: $data['duration_id'],
            amountCents: $amountCents,
            termsVersion: 'v1.0',
        );

        $investment = $this->investmentService->create($request->user(), $account, $dto);

        return response()->json(['data' => $investment->load(['plan', 'duration'])], 201);
    }

    /**
     * GET /api/v1/investments/{investment}
     */
    public function show(Request $request, Investment $investment): JsonResponse
    {
        if ($investment->user_id !== $request->user()->id) {
            abort(403, 'Forbidden');
        }

        return response()->json(['data' => $investment->load(['plan', 'duration', 'account'])]);
    }

    /**
     * POST /api/v1/investments/{investment}/cancel
     */
    public function cancel(Request $request, Investment $investment): JsonResponse
    {
        if ($investment->user_id !== $request->user()->id) {
            abort(403, 'Forbidden');
        }

        $updated = $this->investmentService->cancel($investment);

        return response()->json(['data' => $updated]);
    }
}
