<?php

namespace App\Http\Controllers\Investment;

use App\Http\Controllers\Controller;
use App\Models\InvestmentPlan;
use Illuminate\Http\JsonResponse;

class PlanController extends Controller
{
    /**
     * GET /api/v1/plans
     * Active plans — trending first, then newest.
     */
    public function index(): JsonResponse
    {
        $plans = InvestmentPlan::with('durations')
            ->where('status', 'active')
            ->orderByDesc('is_trending')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $plans]);
    }

    /**
     * GET /api/v1/plans/{plan}
     */
    public function show(InvestmentPlan $plan): JsonResponse
    {
        if ($plan->status !== 'active') {
            abort(404);
        }

        return response()->json(['data' => $plan->load('durations')]);
    }
}
