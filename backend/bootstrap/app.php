<?php

use App\Exceptions\AccountLockedException;
use App\Exceptions\InsufficientFundsException;
use App\Exceptions\InvalidStateTransitionException;
use App\Exceptions\RateLimitExceededException;
use App\Exceptions\TermsNotAcceptedException;
use App\Exceptions\TransactionPendingReviewException;
use App\Exceptions\WithdrawalLimitExceededException;
use App\Http\Middleware\AuditLogMiddleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', AuditLogMiddleware::class);
        $middleware->appendToGroup('api', AuditLogMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // InsufficientFundsException → 422
        $exceptions->renderable(function (InsufficientFundsException $e): JsonResponse {
            return response()->json([
                'message' => $e->getMessage() ?: 'Insufficient funds',
                'code'    => 'INSUFFICIENT_FUNDS',
            ], 422);
        });

        // WithdrawalLimitExceededException → 422
        $exceptions->renderable(function (WithdrawalLimitExceededException $e): JsonResponse {
            return response()->json([
                'message'         => $e->getMessage(),
                'code'            => 'WITHDRAWAL_LIMIT_EXCEEDED',
                'remaining_cents' => $e->remainingCents,
                'resets_at'       => $e->resetsAt->toIso8601String(),
            ], 422);
        });

        // InvalidStateTransitionException → 422
        $exceptions->renderable(function (InvalidStateTransitionException $e): JsonResponse {
            return response()->json([
                'message' => $e->getMessage(),
                'code'    => 'INVALID_STATE_TRANSITION',
                'from'    => $e->fromState,
                'to'      => $e->toState,
            ], 422);
        });

        // AccountLockedException → 403
        $exceptions->renderable(function (AccountLockedException $e): JsonResponse {
            return response()->json([
                'message' => $e->getMessage() ?: 'Account is locked',
                'code'    => 'ACCOUNT_LOCKED',
            ], 403);
        });

        // TermsNotAcceptedException → 403
        $exceptions->renderable(function (TermsNotAcceptedException $e): JsonResponse {
            return response()->json([
                'message' => $e->getMessage() ?: 'Terms not accepted',
                'code'    => 'TERMS_NOT_ACCEPTED',
            ], 403);
        });

        // RateLimitExceededException → 429
        $exceptions->renderable(function (RateLimitExceededException $e): JsonResponse {
            return response()->json([
                'message'     => $e->getMessage(),
                'code'        => 'RATE_LIMIT_EXCEEDED',
                'retry_after' => $e->retryAfter,
            ], 429)->withHeaders(['Retry-After' => $e->retryAfter]);
        });

        // TransactionPendingReviewException → 422
        $exceptions->renderable(function (TransactionPendingReviewException $e): JsonResponse {
            return response()->json([
                'message' => $e->getMessage() ?: 'Transaction is pending review',
                'code'    => 'TRANSACTION_PENDING_REVIEW',
            ], 422);
        });

        // Validation errors → 422
        $exceptions->renderable(function (ValidationException $e): JsonResponse {
            return response()->json([
                'message' => $e->getMessage(),
                'errors'  => $e->errors(),
            ], 422);
        });

        // Authentication errors → 401
        $exceptions->renderable(function (AuthenticationException $e): JsonResponse {
            return response()->json([
                'message' => 'Unauthenticated',
                'code'    => 'UNAUTHENTICATED',
            ], 401);
        });

        // 403 Forbidden
        $exceptions->renderable(function (AccessDeniedHttpException $e): JsonResponse {
            return response()->json([
                'message' => 'Forbidden',
                'code'    => 'FORBIDDEN',
            ], 403);
        });

        // 404 Not Found
        $exceptions->renderable(function (NotFoundHttpException $e): JsonResponse {
            return response()->json([
                'message' => 'Not found',
                'code'    => 'NOT_FOUND',
            ], 404);
        });

        // All other exceptions in production → 500
        $exceptions->renderable(function (\Throwable $e): ?JsonResponse {
            if (app()->environment('production')) {
                return response()->json([
                    'message' => 'An unexpected error occurred',
                    'code'    => 'INTERNAL_ERROR',
                ], 500);
            }

            return null; // Let Laravel's default handler show the stack trace in non-production
        });

    })->create();
