<?php

namespace App\Services;

use App\Contracts\AnalyticsEngineInterface;
use App\Models\Investment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnalyticsEngine implements AnalyticsEngineInterface
{
    private const USER_METRICS_TTL    = 60;   // seconds
    private const PLATFORM_METRICS_TTL = 300; // 5 minutes

    // ----------------------------------------------------------------
    // User metrics
    // ----------------------------------------------------------------

    public function userMetrics(int $userId): array
    {
        $cacheKey = "analytics:user:{$userId}";

        return Cache::remember($cacheKey, self::USER_METRICS_TTL, function () use ($userId) {
            return $this->computeUserMetrics($userId);
        });
    }

    public function userTimeSeries(int $userId, string $granularity, Carbon $from, Carbon $to): array
    {
        $format      = $this->dateFormat($granularity);
        $dateExpr    = DB::connection()->getDriverName() === 'mysql'
            ? "DATE_FORMAT(created_at, '{$format}')"
            : "strftime('{$format}', created_at)";

        return DB::table('investments')
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$from, $to])
            ->whereIn('investments.status', ['active', 'completed'])
            ->selectRaw("{$dateExpr} as period")
            ->selectRaw('SUM(amount_cents) as invested_cents')
            ->selectRaw('SUM(profit_cents) as profit_cents')
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(fn ($row) => [
                'period'         => $row->period,
                'invested_cents' => (int) $row->invested_cents,
                'profit_cents'   => (int) $row->profit_cents,
            ])
            ->toArray();
    }

    // ----------------------------------------------------------------
    // Platform metrics (admin)
    // ----------------------------------------------------------------

    public function platformMetrics(): array
    {
        return Cache::remember('analytics:platform', self::PLATFORM_METRICS_TTL, function () {
            return $this->computePlatformMetrics();
        });
    }

    public function platformTimeSeries(string $granularity, Carbon $from, Carbon $to): array
    {
        $format   = $this->dateFormat($granularity);
        $dateExpr = DB::connection()->getDriverName() === 'mysql'
            ? "DATE_FORMAT(created_at, '{$format}')"
            : "strftime('{$format}', created_at)";

        return DB::table('investments')
            ->whereBetween('created_at', [$from, $to])
            ->whereIn('investments.status', ['active', 'completed'])
            ->selectRaw("{$dateExpr} as period")
            ->selectRaw('COUNT(*) as investment_count')
            ->selectRaw('SUM(amount_cents) as invested_cents')
            ->selectRaw('SUM(profit_cents) as profit_cents')
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(fn ($row) => [
                'period'           => $row->period,
                'investment_count' => (int) $row->investment_count,
                'invested_cents'   => (int) $row->invested_cents,
                'profit_cents'     => (int) $row->profit_cents,
            ])
            ->toArray();
    }

    // ----------------------------------------------------------------
    // Cache invalidation
    // ----------------------------------------------------------------

    public function invalidateUserCache(int $userId): void
    {
        Cache::forget("analytics:user:{$userId}");
    }

    public function invalidatePlatformCache(): void
    {
        Cache::forget('analytics:platform');
    }

    // ----------------------------------------------------------------
    // Private computation methods
    // ----------------------------------------------------------------

    private function dateFormat(string $granularity): string
    {
        $isMysql = DB::connection()->getDriverName() === 'mysql';

        if ($isMysql) {
            return match ($granularity) {
                'weekly'  => '%Y-%u',
                'monthly' => '%Y-%m',
                default   => '%Y-%m-%d',
            };
        }

        // SQLite (used in tests)
        return match ($granularity) {
            'weekly'  => '%Y-%W',
            'monthly' => '%Y-%m',
            default   => '%Y-%m-%d',
        };
    }

    private function computeUserMetrics(int $userId): array
    {
        $totals = DB::table('investments')
            ->where('user_id', $userId)
            ->whereIn('investments.status', ['active', 'completed'])
            ->selectRaw('SUM(amount_cents) as total_invested')
            ->selectRaw('SUM(CASE WHEN investments.status = ? AND result = ? THEN profit_cents ELSE 0 END) as total_profit', ['completed', 'WIN'])
            ->selectRaw('COUNT(CASE WHEN investments.status = ? THEN 1 END) as active_count', ['active'])
            ->first();

        $totalInvested = (int) ($totals->total_invested ?? 0);
        $totalProfit   = (int) ($totals->total_profit ?? 0);
        $activeCount   = (int) ($totals->active_count ?? 0);
        $roiPct        = $totalInvested > 0
            ? round(($totalProfit / $totalInvested) * 100, 2)
            : 0.0;

        // Plan distribution
        $distribution = DB::table('investments')
            ->where('user_id', $userId)
            ->whereIn('investments.status', ['active', 'completed'])
            ->join('investment_plans', 'investments.plan_id', '=', 'investment_plans.id')
            ->selectRaw('investments.plan_id, investment_plans.name as plan_name, SUM(investments.amount_cents) as total')
            ->groupBy('investments.plan_id', 'investment_plans.name')
            ->get()
            ->map(fn ($row) => [
                'plan_id'    => $row->plan_id,
                'plan_name'  => $row->plan_name,
                'total_cents'=> (int) $row->total,
                'percentage' => $totalInvested > 0
                    ? round(($row->total / $totalInvested) * 100, 2)
                    : 0.0,
            ])
            ->toArray();

        return [
            'total_invested_cents' => $totalInvested,
            'total_invested_usd'   => number_format($totalInvested / 100, 2),
            'total_profit_cents'   => $totalProfit,
            'total_profit_usd'     => number_format($totalProfit / 100, 2),
            'roi_percentage'       => $roiPct,
            'active_investments'   => $activeCount,
            'plan_distribution'    => $distribution,
            'computed_at'          => now()->toIso8601String(),
        ];
    }

    private function computePlatformMetrics(): array
    {
        $totals = DB::table('investments')
            ->selectRaw('COUNT(*) as total_count')
            ->selectRaw('SUM(amount_cents) as total_invested')
            ->selectRaw('SUM(CASE WHEN investments.status = ? AND result = ? THEN profit_cents ELSE 0 END) as total_profit_paid', ['completed', 'WIN'])
            ->first();

        // Active users by period
        $activeUsers = [
            'last_24h'  => User::where('updated_at', '>=', now()->subDay())->count(),
            'last_7d'   => User::where('updated_at', '>=', now()->subWeek())->count(),
            'last_30d'  => User::where('updated_at', '>=', now()->subMonth())->count(),
        ];

        // Top 5 plans by total invested
        $topPlans = DB::table('investments')
            ->whereIn('investments.status', ['active', 'completed'])
            ->join('investment_plans', 'investments.plan_id', '=', 'investment_plans.id')
            ->selectRaw('investments.plan_id, investment_plans.name, SUM(investments.amount_cents) as total, COUNT(*) as count')
            ->groupBy('investments.plan_id', 'investment_plans.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'plan_id'     => $row->plan_id,
                'plan_name'   => $row->name,
                'total_cents' => (int) $row->total,
                'count'       => (int) $row->count,
            ])
            ->toArray();

        // Plan distribution percentages
        $totalInvested = (int) ($totals->total_invested ?? 0);
        $planDist = DB::table('investments')
            ->whereIn('investments.status', ['active', 'completed'])
            ->join('investment_plans', 'investments.plan_id', '=', 'investment_plans.id')
            ->selectRaw('investments.plan_id, investment_plans.name, SUM(investments.amount_cents) as total')
            ->groupBy('investments.plan_id', 'investment_plans.name')
            ->get()
            ->map(fn ($row) => [
                'plan_id'    => $row->plan_id,
                'plan_name'  => $row->name,
                'percentage' => $totalInvested > 0
                    ? round(($row->total / $totalInvested) * 100, 2)
                    : 0.0,
            ])
            ->toArray();

        return [
            'total_investments'       => (int) ($totals->total_count ?? 0),
            'total_invested_cents'    => $totalInvested,
            'total_invested_usd'      => number_format($totalInvested / 100, 2),
            'total_profit_paid_cents' => (int) ($totals->total_profit_paid ?? 0),
            'total_profit_paid_usd'   => number_format(($totals->total_profit_paid ?? 0) / 100, 2),
            'active_users'            => $activeUsers,
            'top_plans'               => $topPlans,
            'plan_distribution'       => $planDist,
            'computed_at'             => now()->toIso8601String(),
        ];
    }
}
