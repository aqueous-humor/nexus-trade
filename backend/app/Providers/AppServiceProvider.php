<?php

namespace App\Providers;

use App\Contracts\AnalyticsEngineInterface;
use App\Contracts\FraudDetectorInterface;
use App\Contracts\InvestmentServiceInterface;
use App\Contracts\RateLimiterServiceInterface;
use App\Contracts\WalletServiceInterface;
use App\Services\AnalyticsEngine;
use App\Services\FraudDetector;
use App\Services\InvestmentService;
use App\Services\RateLimiterService;
use App\Services\WalletService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(WalletServiceInterface::class, WalletService::class);
        $this->app->bind(FraudDetectorInterface::class, FraudDetector::class);
        $this->app->bind(RateLimiterServiceInterface::class, RateLimiterService::class);
        $this->app->bind(InvestmentServiceInterface::class, InvestmentService::class);
        $this->app->bind(AnalyticsEngineInterface::class, AnalyticsEngine::class);
        $this->app->singleton(\App\Services\FraudCheckService::class);
        $this->app->singleton(\App\Services\FeeCalculator::class);
        $this->app->singleton(\App\Services\WithdrawalLimitService::class);
        $this->app->singleton(\App\Services\NotificationService::class);
    }

    public function boot(): void
    {
        // Admin gate — used by Route::middleware('can:admin')
        Gate::define('admin', fn ($user) => $user->isAdmin());

        // Morph map for polymorphic relationships
        \Illuminate\Database\Eloquent\Relations\Relation::morphMap([
            'transaction' => \App\Models\Transaction::class,
            'investment'  => \App\Models\Investment::class,
        ]);

        // Event listeners
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\InvestmentStatusChanged::class,
            \App\Listeners\InvalidateAnalyticsCache::class,
        );
    }
}
