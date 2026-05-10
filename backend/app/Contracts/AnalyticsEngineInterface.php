<?php

namespace App\Contracts;

use Carbon\Carbon;

interface AnalyticsEngineInterface
{
    public function userMetrics(int $userId): array;

    public function userTimeSeries(int $userId, string $granularity, Carbon $from, Carbon $to): array;

    public function platformMetrics(): array;

    public function platformTimeSeries(string $granularity, Carbon $from, Carbon $to): array;
}
