<?php

namespace App\Http\Middleware;

use App\Services\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditLogMiddleware
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    /**
     * Perform post-response actions (terminable middleware).
     * Only logs mutating HTTP methods: POST, PATCH, PUT, DELETE.
     */
    public function terminate(Request $request, Response $response): void
    {
        $method = strtoupper($request->method());

        if (! in_array($method, ['POST', 'PATCH', 'PUT', 'DELETE'], true)) {
            return;
        }

        $user = auth()->user();

        if ($user !== null) {
            $actorId   = $user->id;
            $actorType = $user->isAdmin() ? 'admin' : 'user';
        } else {
            $actorId   = null;
            $actorType = 'system';
        }

        $routeName     = $request->route()?->getName() ?? 'unknown';
        $operationType = "{$method}:{$routeName}";
        $outcome       = (string) $response->getStatusCode();

        $this->auditLogger->log(
            operationType: $operationType,
            actorType: $actorType,
            actorId: $actorId,
            targetType: null,
            targetId: null,
            outcome: $outcome,
        );
    }
}
