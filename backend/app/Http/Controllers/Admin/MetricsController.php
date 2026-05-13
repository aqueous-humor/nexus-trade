<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\AnalyticsEngineInterface;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class MetricsController extends Controller
{
    public function __construct(private readonly AnalyticsEngineInterface $engine) {}

    /**
     * GET /api/v1/admin/metrics
     *
     * Returns a dashboard-ready metrics payload shaped for the frontend.
     * Combines platform metrics + last-30-day investment growth timeseries.
     */
    public function index(): JsonResponse
    {
        $platform = $this->engine->platformMetrics();
        $growth   = $this->engine->platformTimeSeries(
            granularity: 'daily',
            from: Carbon::now()->subDays(29)->startOfDay(),
            to:   Carbon::now()->endOfDay(),
        );

        $activeUsers = $platform['active_users'];

        return response()->json([
            'data' => [
                // Investments
                'total_investments'       => $platform['total_investments'],
                'total_value_cents'       => $platform['total_invested_cents'],
                'total_profit_paid_cents' => $platform['total_profit_paid_cents'],

                // Active users (flat keys for frontend compatibility)
                'active_users'            => $activeUsers['last_30d'] ?? 0,
                'active_users_last_24h'   => $activeUsers['last_24h']  ?? 0,
                'active_users_last_7d'    => $activeUsers['last_7d']   ?? 0,
                'active_users_last_30d'   => $activeUsers['last_30d']  ?? 0,

                // Investment growth for chart (date + total invested that day in cents)
                'investment_growth'       => array_map(
                    static fn (array $row): array => [
                        'date'  => $row['period'],
                        'value' => $row['invested_cents'],
                    ],
                    $growth,
                ),

                // Top plans shaped for table display
                'top_plans' => array_map(
                    static fn (array $p): array => [
                        'name'           => $p['plan_name'],
                        'total_invested' => number_format($p['total_cents'] / 100, 2),
                        'count'          => $p['count'],
                    ],
                    $platform['top_plans'],
                ),
            ],
        ]);
    }
}
