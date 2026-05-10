<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordResetController;
use Illuminate\Support\Facades\Route;

// Health check
Route::get('/health', fn () => response()->json(['status' => 'ok', 'timestamp' => now()->toIso8601String()]));

Route::prefix('v1')->group(function (): void {

    // ----------------------------------------------------------------
    // Guest routes
    // ----------------------------------------------------------------
    Route::prefix('auth')->group(function (): void {
        Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
        Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
        Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword'])->name('auth.forgot-password');
        Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('auth.reset-password');
    });

    // ----------------------------------------------------------------
    // Authenticated routes
    // ----------------------------------------------------------------
    Route::middleware('auth:sanctum')->group(function (): void {

        // Auth
        Route::prefix('auth')->group(function (): void {
            Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
            Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
        });

        // Email verification
        Route::prefix('email')->group(function (): void {
            Route::post('/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
                ->middleware('signed')->name('verification.verify');
            Route::post('/resend', [EmailVerificationController::class, 'resend'])->name('verification.resend');
        });

        // Wallet
        Route::prefix('wallet')->group(function (): void {
            Route::get('/', [\App\Http\Controllers\Wallet\WalletController::class, 'show'])->name('wallet.show');
            Route::get('/transactions', [\App\Http\Controllers\Wallet\WalletController::class, 'transactions'])->name('wallet.transactions');
            Route::post('/deposit', [\App\Http\Controllers\Wallet\DepositController::class, 'initiate'])->name('wallet.deposit');
            Route::post('/deposit/{transaction}/confirm', [\App\Http\Controllers\Wallet\DepositController::class, 'confirm'])->name('wallet.deposit.confirm');
            Route::post('/withdraw', [\App\Http\Controllers\Wallet\WithdrawalController::class, 'store'])->name('wallet.withdraw');
        });

        // Accounts
        Route::prefix('accounts')->group(function (): void {
            Route::get('/', [\App\Http\Controllers\Account\AccountController::class, 'index'])->name('accounts.index');
            Route::post('/', [\App\Http\Controllers\Account\AccountController::class, 'store'])->name('accounts.store');
            Route::get('/{account}', [\App\Http\Controllers\Account\AccountController::class, 'show'])->name('accounts.show');
            Route::delete('/{account}', [\App\Http\Controllers\Account\AccountController::class, 'destroy'])->name('accounts.destroy');
            Route::patch('/{account}/leverage', [\App\Http\Controllers\Account\AccountController::class, 'updateLeverage'])->name('accounts.leverage');
        });

        // Investment Plans (user-facing)
        Route::prefix('plans')->group(function (): void {
            Route::get('/', [\App\Http\Controllers\Investment\PlanController::class, 'index'])->name('plans.index');
            Route::get('/{plan}', [\App\Http\Controllers\Investment\PlanController::class, 'show'])->name('plans.show');
        });

        // Investments
        Route::prefix('investments')->group(function (): void {
            Route::get('/', [\App\Http\Controllers\Investment\InvestmentController::class, 'index'])->name('investments.index');
            Route::post('/', [\App\Http\Controllers\Investment\InvestmentController::class, 'store'])->name('investments.store');
            Route::get('/{investment}', [\App\Http\Controllers\Investment\InvestmentController::class, 'show'])->name('investments.show');
            Route::post('/{investment}/cancel', [\App\Http\Controllers\Investment\InvestmentController::class, 'cancel'])->name('investments.cancel');
        });

        // Terms & Compliance
        Route::prefix('terms')->group(function (): void {
            Route::get('/current', [\App\Http\Controllers\Terms\TermsController::class, 'current'])->name('terms.current');
            Route::post('/accept', [\App\Http\Controllers\Terms\TermsController::class, 'accept'])->name('terms.accept');
        });

        // Signals
        Route::get('/signals', [\App\Http\Controllers\Signal\SignalController::class, 'index'])->name('signals.index');
        Route::post('/accounts/{account}/signal', [\App\Http\Controllers\Signal\AccountSignalController::class, 'store'])->name('accounts.signal.store');
        Route::delete('/accounts/{account}/signal', [\App\Http\Controllers\Signal\AccountSignalController::class, 'destroy'])->name('accounts.signal.destroy');

        // Analytics
        Route::prefix('analytics')->group(function (): void {
            Route::get('/me', [\App\Http\Controllers\Analytics\AnalyticsController::class, 'userMetrics'])->name('analytics.user');
            Route::get('/me/timeseries', [\App\Http\Controllers\Analytics\AnalyticsController::class, 'userTimeSeries'])->name('analytics.user.timeseries');
        });

        // Notification preferences
        Route::prefix('notifications')->group(function (): void {
            Route::get('/preferences', [\App\Http\Controllers\Notification\NotificationPreferencesController::class, 'show'])->name('notifications.preferences.show');
            Route::patch('/preferences', [\App\Http\Controllers\Notification\NotificationPreferencesController::class, 'update'])->name('notifications.preferences.update');
        });

        // Admin routes
        Route::prefix('admin')->name('admin.')->middleware('can:admin')->group(function (): void {
            Route::apiResource('brokers', \App\Http\Controllers\Admin\BrokerController::class);
            Route::apiResource('plans', \App\Http\Controllers\Admin\PlanController::class);
            Route::post('durations', [\App\Http\Controllers\Admin\PlanController::class, 'storeDuration'])->name('durations.store');
            Route::post('plans/{plan}/durations', [\App\Http\Controllers\Admin\PlanController::class, 'attachDuration'])->name('plans.durations.attach');
            Route::delete('plans/{plan}/durations/{duration}', [\App\Http\Controllers\Admin\PlanController::class, 'detachDuration'])->name('plans.durations.detach');
            Route::get('terms', [\App\Http\Controllers\Admin\TermsController::class, 'index'])->name('terms.index');
            Route::post('terms', [\App\Http\Controllers\Admin\TermsController::class, 'store'])->name('terms.store');
            Route::patch('terms/{terms}', [\App\Http\Controllers\Admin\TermsController::class, 'update'])->name('terms.update');
            Route::apiResource('signals', \App\Http\Controllers\Admin\SignalController::class);
            Route::get('signals/{signal}', [\App\Http\Controllers\Admin\SignalController::class, 'show'])->name('signals.show');
            Route::post('signals/{signal}/activate', [\App\Http\Controllers\Admin\SignalController::class, 'activate'])->name('signals.activate');
            Route::post('signals/{signal}/deactivate', [\App\Http\Controllers\Admin\SignalController::class, 'deactivate'])->name('signals.deactivate');
            // Accounts (admin)
            Route::get('accounts', [\App\Http\Controllers\Admin\AccountController::class, 'index'])->name('accounts.index');
            Route::post('accounts', [\App\Http\Controllers\Admin\AccountController::class, 'store'])->name('accounts.store');
            Route::get('accounts/{account}', [\App\Http\Controllers\Admin\AccountController::class, 'show'])->name('accounts.show');
            Route::patch('accounts/{account}', [\App\Http\Controllers\Admin\AccountController::class, 'update'])->name('accounts.update');
            Route::delete('accounts/{account}', [\App\Http\Controllers\Admin\AccountController::class, 'destroy'])->name('accounts.destroy');
            Route::patch('accounts/{account}/status', [\App\Http\Controllers\Admin\AccountController::class, 'updateStatus'])->name('accounts.status');
            Route::patch('accounts/{account}/reassign', [\App\Http\Controllers\Admin\AccountController::class, 'reassign'])->name('accounts.reassign');
            // Users
            Route::get('users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');
            Route::get('users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'show'])->name('users.show');
            Route::patch('users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'update'])->name('users.update');
            // Fraud review
            Route::get('fraud', [\App\Http\Controllers\Admin\FraudController::class, 'index'])->name('fraud.index');
            Route::post('fraud/{assessment}/approve', [\App\Http\Controllers\Admin\FraudController::class, 'approve'])->name('fraud.approve');
            Route::post('fraud/{assessment}/reject', [\App\Http\Controllers\Admin\FraudController::class, 'reject'])->name('fraud.reject');
            // Investments (admin)
            Route::get('investments', [\App\Http\Controllers\Admin\InvestmentController::class, 'index'])->name('investments.index');
            Route::post('investments', [\App\Http\Controllers\Admin\InvestmentController::class, 'store'])->name('investments.store');
            Route::get('investments/{investment}', [\App\Http\Controllers\Admin\InvestmentController::class, 'show'])->name('investments.show');
            Route::patch('investments/{investment}/status', [\App\Http\Controllers\Admin\InvestmentController::class, 'updateStatus'])->name('investments.status');
            Route::patch('investments/{investment}/result', [\App\Http\Controllers\Admin\InvestmentController::class, 'recordResult'])->name('investments.result');
            Route::patch('investments/{investment}/profit', [\App\Http\Controllers\Admin\InvestmentController::class, 'adjustProfit'])->name('investments.profit');
            Route::post('investments/{investment}/recover', [\App\Http\Controllers\Admin\InvestmentController::class, 'recover'])->name('investments.recover');
            // Analytics
            Route::get('analytics', [\App\Http\Controllers\Analytics\AnalyticsController::class, 'platformMetrics'])->name('analytics.platform');
            Route::get('analytics/timeseries', [\App\Http\Controllers\Analytics\AnalyticsController::class, 'platformTimeSeries'])->name('analytics.platform.timeseries');
            // Audit logs
            Route::get('audit-logs', [\App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('audit-logs.index');
        });

    });

});
