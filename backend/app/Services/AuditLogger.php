<?php

namespace App\Services;

use App\Models\AuditLog;

class AuditLogger
{
    /**
     * Create an immutable audit log entry.
     *
     * @param  string      $operationType  e.g. "POST:investments.store"
     * @param  string      $actorType      'user' | 'admin' | 'system'
     * @param  int|null    $actorId        ID of the acting user (null for system)
     * @param  string|null $targetType     Model class or resource type (nullable)
     * @param  int|null    $targetId       ID of the target resource (nullable)
     * @param  string      $outcome        'success' or HTTP status code string
     * @param  array       $metadata       Additional context data
     * @param  string|null $ipAddress      Client IP (auto-detected from request if null)
     * @param  string|null $payloadHash    SHA-256 of request payload (auto-computed if null)
     */
    public function log(
        string $operationType,
        string $actorType,
        ?int $actorId,
        ?string $targetType,
        ?int $targetId,
        string $outcome,
        array $metadata = [],
        ?string $ipAddress = null,
        ?string $payloadHash = null
    ): AuditLog {
        $ipAddress ??= request()->ip();

        if ($payloadHash === null) {
            $content = request()->getContent();
            $payloadHash = $content !== '' ? hash('sha256', $content) : null;
        }

        return AuditLog::create([
            'operation_type' => $operationType,
            'actor_type'     => $actorType,
            'actor_id'       => $actorId,
            'target_type'    => $targetType,
            'target_id'      => $targetId,
            'ip_address'     => $ipAddress,
            'payload_hash'   => $payloadHash,
            'outcome'        => $outcome,
            'metadata'       => $metadata ?: null,
        ]);
    }
}
