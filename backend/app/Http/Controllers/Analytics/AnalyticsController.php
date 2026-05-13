<?php

namespace App\Http\Controllers\Analytics;

use App\Contracts\AnalyticsEngineInterface;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(private readonly AnalyticsEngineInterface $engine) {}

    /**
     * GET /api/v1/analytics/me
     */
    public function userMetrics(Request $request): JsonResponse
    {
        $raw = $this->engine->userMetrics($request->user()->id);

        return response()->json(['data' => [
            'total_invested_cents' => $raw['total_invested_cents'],
            'total_profit_cents'   => $raw['total_profit_cents'],
            'roi_percentage'       => $raw['roi_percentage'],
            'active_investments'   => $raw['active_investments'],
            'plan_distribution'    => $raw['plan_distribution'],
            'computed_at'          => $raw['computed_at'],
        ]]);
    }

    /**
     * GET /api/v1/analytics/me/timeseries
     */
    public function userTimeSeries(Request $request): JsonResponse
    {
        $request->validate([
            'granularity' => ['nullable', 'in:daily,weekly,monthly'],
            'from'        => ['nullable', 'date'],
            'to'          => ['nullable', 'date'],
        ]);

        $granularity = $request->granularity ?? 'daily';
        $from        = $request->from ? Carbon::parse($request->from) : now()->subMonth();
        $to          = $request->to   ? Carbon::parse($request->to)   : now();

        $rows = $this->engine->userTimeSeries($request->user()->id, $granularity, $from, $to);

        // Normalize to { date, value } for frontend chart compatibility
        $data = array_map(static fn (array $row): array => [
            'date'  => $row['period'],
            'value' => $row['invested_cents'],
        ], $rows);

        return response()->json(['data' => $data]);
    }

    /**
     * GET /api/v1/admin/analytics
     */
    public function platformMetrics(): JsonResponse
    {
        return response()->json(['data' => $this->engine->platformMetrics()]);
    }

    /**
     * GET /api/v1/admin/analytics/timeseries
     */
    public function platformTimeSeries(Request $request): JsonResponse
    {
        $request->validate([
            'granularity' => ['nullable', 'in:daily,weekly,monthly'],
            'from'        => ['nullable', 'date'],
            'to'          => ['nullable', 'date'],
        ]);

        $granularity = $request->granularity ?? 'daily';
        $from        = $request->from ? Carbon::parse($request->from) : now()->subMonth();
        $to          = $request->to   ? Carbon::parse($request->to)   : now();

        $data = $this->engine->platformTimeSeries($granularity, $from, $to);

        return response()->json(['data' => $data]);
    }
}
