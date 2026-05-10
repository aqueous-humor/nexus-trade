<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class AuditLog extends Model
{
    // Audit logs are append-only — no updated_at column
    public $timestamps = false;

    const CREATED_AT = 'created_at';

    protected $fillable = [
        'operation_type',
        'actor_type',
        'actor_id',
        'target_type',
        'target_id',
        'ip_address',
        'payload_hash',
        'outcome',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata'   => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Prevent any updates to audit log entries.
     * Audit logs are append-only and must never be modified.
     */
    public function save(array $options = []): bool
    {
        if ($this->exists) {
            throw new RuntimeException('Audit log entries are immutable and cannot be modified.');
        }

        return parent::save($options);
    }

    /**
     * Prevent updates to audit log entries.
     * Audit logs are append-only and must never be modified.
     */
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new RuntimeException('Audit log entries are immutable and cannot be modified.');
    }

    /**
     * Prevent deletion of audit log entries.
     * Audit logs are append-only and must never be deleted.
     */
    public function delete(): ?bool
    {
        throw new RuntimeException('Audit log entries are immutable and cannot be deleted.');
    }

    /**
     * Prevent force deletion of audit log entries.
     * Audit logs are append-only and must never be deleted.
     */
    public function forceDelete(): ?bool
    {
        throw new RuntimeException('Audit log entries are immutable and cannot be deleted.');
    }

    // Relationships

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
