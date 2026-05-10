<?php

namespace Tests\Unit\Mail;

use App\Mail\InvestmentCreatedMail;
use App\Models\AuditLog;
use App\Models\Investment;
use App\Services\AuditLogger;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogsMailFailureTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_method_logs_to_audit_log_via_audit_logger(): void
    {
        // Arrange: Create a mailable instance
        $investment = Investment::factory()->create();
        $mailable = new InvestmentCreatedMail($investment);
        
        $exception = new Exception('SMTP connection failed');

        // Act: Call the failed method
        $mailable->failed($exception);

        // Assert: Check that an audit log entry was created
        $this->assertDatabaseHas('audit_logs', [
            'operation_type' => 'email.failed',
            'actor_type'     => 'system',
            'actor_id'       => null,
            'target_type'    => InvestmentCreatedMail::class,
            'outcome'        => Exception::class,
        ]);

        $log = AuditLog::where('operation_type', 'email.failed')->first();
        $this->assertNotNull($log);
        $this->assertEquals(InvestmentCreatedMail::class, $log->metadata['mailable']);
        $this->assertEquals('SMTP connection failed', $log->metadata['error']);
    }

    public function test_mailable_has_correct_queue_configuration(): void
    {
        // Arrange
        $investment = Investment::factory()->create();
        $mailable = new InvestmentCreatedMail($investment);

        // Assert: Check queue configuration
        $this->assertEquals(3, $mailable->tries);
        $this->assertEquals([30, 300, 1800], $mailable->backoff);
    }
}
