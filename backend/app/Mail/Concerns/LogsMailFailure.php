<?php

namespace App\Mail\Concerns;

use App\Services\AuditLogger;
use Throwable;

/**
 * Adds a failed() hook to any Mailable that logs delivery failures
 * to the audit_log after all retry attempts are exhausted.
 */
trait LogsMailFailure
{
    public function failed(Throwable $exception): void
    {
        /** @var AuditLogger $logger */
        $logger = app(AuditLogger::class);

        $logger->log(
            operationType: 'email.failed',
            actorType: 'system',
            actorId: null,
            targetType: static::class,
            targetId: null,
            outcome: get_class($exception),
            metadata: [
                'mailable' => static::class,
                'error'    => $exception->getMessage(),
            ],
            ipAddress: null,
            payloadHash: null,
        );
    }
}
