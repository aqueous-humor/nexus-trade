<?php

namespace App\Listeners;

use App\Events\InvestmentStatusChanged;
use App\Services\AnalyticsEngine;

class InvalidateAnalyticsCache
{
    public function __construct(private readonly AnalyticsEngine $engine) {}

    public function handle(InvestmentStatusChanged $event): void
    {
        $this->engine->invalidateUserCache($event->investment->user_id);
        $this->engine->invalidatePlatformCache();
    }
}
