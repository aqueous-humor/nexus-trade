<?php

namespace Tests\Property;

use App\Models\AuditLog;
use App\Models\User;
use Eris\Generator;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-based tests for AuditLog immutability.
 *
 * Feature: forex-broker-platform
 * **Validates: Requirements 19**
 */
class AuditLogImmutabilityPropertyTest extends TestCase
{
    use RefreshDatabase;
    use TestTrait;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * P19: Audit log entries cannot be modified or deleted.
     *
     * For any audit log entry created, no application-level operation
     * SHALL modify or delete it. The audit log is append-only.
     *
     * **Validates: Requirements 19.4**
     */
    public function test_audit_log_entries_cannot_be_modified_or_deleted(): void
    {
        $this->minimumEvaluationRatio(0.5);

        $this->forAll(
            Generator\elements('POST:investments.store', 'PATCH:accounts.update', 'DELETE:accounts.destroy', 'POST:deposits.store', 'POST:withdrawals.store'),
            Generator\elements('user', 'admin', 'system'),
            Generator\elements('success', '400', '401', '403', '404', '422', '500'),
            Generator\elements('App\Models\Investment', 'App\Models\Account', 'App\Models\Transaction', null),
            Generator\choose(1, 10000)
        )
        ->withMaxSize(50)
        ->then(function (
            string $operationType,
            string $actorType,
            string $outcome,
            ?string $targetType,
            int $targetId
        ) {
            // Create a user for actor_id reference
            $user = User::factory()->create();

            // Create an audit log entry
            $auditLog = AuditLog::create([
                'operation_type' => $operationType,
                'actor_type'     => $actorType,
                'actor_id'       => $user->id,
                'target_type'    => $targetType,
                'target_id'      => $targetId,
                'ip_address'     => '192.168.1.' . rand(1, 254),
                'payload_hash'   => hash('sha256', 'test-payload-' . rand(1, 1000)),
                'outcome'        => $outcome,
                'metadata'       => ['test' => 'data', 'random' => rand(1, 1000)],
            ]);

            $originalId = $auditLog->id;
            $originalOperationType = $auditLog->operation_type;
            $originalActorType = $auditLog->actor_type;
            $originalActorId = $auditLog->actor_id;
            $originalOutcome = $auditLog->outcome;
            $originalTargetType = $auditLog->target_type;
            $originalTargetId = $auditLog->target_id;
            $originalIpAddress = $auditLog->ip_address;
            $originalPayloadHash = $auditLog->payload_hash;
            $originalMetadata = $auditLog->metadata;

            // Attempt 1: Try to update the audit log entry using update()
            try {
                $auditLog->update([
                    'operation_type' => 'MODIFIED:operation',
                    'outcome'        => 'modified',
                    'actor_type'     => 'modified',
                ]);
            } catch (\Exception $e) {
                // If an exception is thrown, that's acceptable (model prevents updates)
            }

            // Refresh from database
            $auditLog->refresh();

            // Assert that all fields remain unchanged
            $this->assertEquals($originalId, $auditLog->id, 'ID should not change');
            $this->assertEquals($originalOperationType, $auditLog->operation_type, 'operation_type should not change');
            $this->assertEquals($originalActorType, $auditLog->actor_type, 'actor_type should not change');
            $this->assertEquals($originalActorId, $auditLog->actor_id, 'actor_id should not change');
            $this->assertEquals($originalOutcome, $auditLog->outcome, 'outcome should not change');
            $this->assertEquals($originalTargetType, $auditLog->target_type, 'target_type should not change');
            $this->assertEquals($originalTargetId, $auditLog->target_id, 'target_id should not change');
            $this->assertEquals($originalIpAddress, $auditLog->ip_address, 'ip_address should not change');
            $this->assertEquals($originalPayloadHash, $auditLog->payload_hash, 'payload_hash should not change');
            $this->assertEquals($originalMetadata, $auditLog->metadata, 'metadata should not change');

            // Attempt 2: Try to update using save() after modifying attributes
            try {
                $auditLog->operation_type = 'MODIFIED:operation2';
                $auditLog->outcome = 'modified2';
                $auditLog->save();
            } catch (\Exception $e) {
                // If an exception is thrown, that's acceptable
            }

            // Refresh from database again
            $auditLog->refresh();

            // Assert that all fields still remain unchanged
            $this->assertEquals($originalOperationType, $auditLog->operation_type, 'operation_type should still not change after save()');
            $this->assertEquals($originalOutcome, $auditLog->outcome, 'outcome should still not change after save()');

            // Attempt 3: Try to delete the audit log entry using delete()
            try {
                $auditLog->delete();
            } catch (\Exception $e) {
                // If an exception is thrown, that's acceptable (model prevents deletes)
            }

            // Verify the record still exists in the database
            $stillExists = AuditLog::where('id', $originalId)->exists();
            $this->assertTrue($stillExists, 'Audit log entry should still exist after delete() attempt');

            // Attempt 4: Try to force delete
            try {
                $auditLog->forceDelete();
            } catch (\Exception $e) {
                // If an exception is thrown, that's acceptable
            }

            // Verify the record still exists in the database
            $stillExists = AuditLog::where('id', $originalId)->exists();
            $this->assertTrue($stillExists, 'Audit log entry should still exist after forceDelete() attempt');

            // Final verification: fetch fresh from DB and verify all original values
            $finalCheck = AuditLog::find($originalId);
            $this->assertNotNull($finalCheck, 'Audit log entry should be retrievable');
            $this->assertEquals($originalOperationType, $finalCheck->operation_type, 'Final check: operation_type unchanged');
            $this->assertEquals($originalActorType, $finalCheck->actor_type, 'Final check: actor_type unchanged');
            $this->assertEquals($originalOutcome, $finalCheck->outcome, 'Final check: outcome unchanged');
        });
    }
}
