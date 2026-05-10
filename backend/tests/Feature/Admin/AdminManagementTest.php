<?php

namespace Tests\Feature\Admin;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Duration;
use App\Models\Investment;
use App\Models\InvestmentPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->regularUser = User::factory()->create(['role' => 'user']);
    }

    // ----------------------------------------------------------------
    // Admin Account CRUD tests
    // ----------------------------------------------------------------

    public function test_admin_can_list_all_accounts(): void
    {
        Account::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/accounts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'current_page',
                'per_page',
                'total',
            ]);
    }

    public function test_admin_can_filter_accounts_by_status(): void
    {
        Account::factory()->count(2)->create(['status' => 'active']);
        Account::factory()->count(2)->create(['status' => 'suspended']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/accounts?status=active');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
        foreach ($data as $account) {
            $this->assertEquals('active', $account['status']);
        }
    }

    public function test_admin_can_create_account_for_user(): void
    {
        $targetUser = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/accounts', [
                'user_id' => $targetUser->id,
                'type'    => 'demo',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.user_id', $targetUser->id)
            ->assertJsonPath('data.type', 'demo');

        $this->assertDatabaseHas('accounts', [
            'user_id' => $targetUser->id,
            'type'    => 'demo',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'operation_type' => 'admin.account.created',
            'actor_id'       => $this->admin->id,
            'outcome'        => 'success',
        ]);
    }

    public function test_admin_can_update_account_status(): void
    {
        $account = Account::factory()->create(['status' => 'active']);

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/v1/admin/accounts/{$account->id}/status", [
                'status' => 'suspended',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'suspended');

        $this->assertDatabaseHas('accounts', [
            'id'     => $account->id,
            'status' => 'suspended',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'operation_type' => 'admin.account.status_changed',
            'actor_id'       => $this->admin->id,
            'outcome'        => 'success',
        ]);
    }

    public function test_admin_can_reassign_account_to_different_user(): void
    {
        $originalUser = User::factory()->create(['role' => 'user']);
        $newUser = User::factory()->create(['role' => 'user']);
        $account = Account::factory()->create(['user_id' => $originalUser->id]);

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/v1/admin/accounts/{$account->id}/reassign", [
                'new_user_id' => $newUser->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.user_id', $newUser->id);

        $this->assertDatabaseHas('accounts', [
            'id'      => $account->id,
            'user_id' => $newUser->id,
        ]);

        // Verify audit log contains old and new user IDs in metadata
        $auditLog = AuditLog::where('operation_type', 'admin.account.reassigned')
            ->where('actor_id', $this->admin->id)
            ->latest('created_at')
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals($originalUser->id, $auditLog->metadata['old_user_id']);
        $this->assertEquals($newUser->id, $auditLog->metadata['new_user_id']);
    }

    public function test_admin_can_soft_delete_account(): void
    {
        $account = Account::factory()->create();

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/v1/admin/accounts/{$account->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('accounts', ['id' => $account->id]);

        $this->assertDatabaseHas('audit_logs', [
            'operation_type' => 'admin.account.deleted',
            'actor_id'       => $this->admin->id,
            'outcome'        => 'success',
        ]);
    }

    public function test_non_admin_cannot_access_admin_accounts(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->getJson('/api/v1/admin/accounts');

        $response->assertStatus(403);
    }

    // ----------------------------------------------------------------
    // Admin Investment tests
    // ----------------------------------------------------------------

    public function test_admin_can_list_all_investments_with_filters(): void
    {
        $targetUser = User::factory()->create(['role' => 'user']);
        $account = Account::factory()->create(['user_id' => $targetUser->id]);
        $plan = InvestmentPlan::factory()->create();
        $duration = Duration::factory()->create();

        Investment::factory()->count(2)->create([
            'user_id'    => $targetUser->id,
            'account_id' => $account->id,
            'plan_id'    => $plan->id,
            'duration_id' => $duration->id,
        ]);

        // Create investments for another user
        Investment::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/admin/investments?user_id={$targetUser->id}");

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(2, $data);
        foreach ($data as $investment) {
            $this->assertEquals($targetUser->id, $investment['user_id']);
        }
    }

    public function test_admin_can_manually_create_investment(): void
    {
        $targetUser = User::factory()->create(['role' => 'user']);
        $account = Account::factory()->create(['user_id' => $targetUser->id]);
        $plan = InvestmentPlan::factory()->create();
        $duration = Duration::factory()->create();

        // Link duration to plan
        \Illuminate\Support\Facades\DB::table('plan_durations')->insert([
            'plan_id'     => $plan->id,
            'duration_id' => $duration->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/investments', [
                'user_id'      => $targetUser->id,
                'account_id'   => $account->id,
                'plan_id'      => $plan->id,
                'duration_id'  => $duration->id,
                'amount_cents' => 50000,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.user_id', $targetUser->id)
            ->assertJsonPath('data.created_by_admin', true);

        $this->assertDatabaseHas('investments', [
            'user_id'          => $targetUser->id,
            'account_id'       => $account->id,
            'created_by_admin' => true,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'operation_type' => 'admin.investment.created',
            'actor_id'       => $this->admin->id,
            'outcome'        => 'success',
        ]);
    }

    public function test_admin_can_adjust_profit_with_reason(): void
    {
        $investment = Investment::factory()->create([
            'profit_cents' => 10000,
            'status'       => 'completed',
            'result'       => 'WIN',
        ]);

        $adjustedProfit = 12000;
        $reason = 'Manual correction due to calculation error';

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/v1/admin/investments/{$investment->id}/profit", [
                'adjusted_profit_cents' => $adjustedProfit,
                'reason'                => $reason,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.adjusted_profit_cents', $adjustedProfit);

        $this->assertDatabaseHas('investments', [
            'id'                    => $investment->id,
            'adjusted_profit_cents' => $adjustedProfit,
        ]);

        // Verify audit log contains required metadata fields
        $auditLog = AuditLog::where('operation_type', 'admin.investment.profit_adjusted')
            ->where('actor_id', $this->admin->id)
            ->latest('created_at')
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals(10000, $auditLog->metadata['original_profit_cents']);
        $this->assertEquals($adjustedProfit, $auditLog->metadata['adjusted_profit_cents']);
        $this->assertEquals($this->admin->id, $auditLog->metadata['admin_id']);
        $this->assertEquals($reason, $auditLog->metadata['reason']);
    }

    // ----------------------------------------------------------------
    // Audit log tests
    // ----------------------------------------------------------------

    public function test_admin_can_query_audit_log(): void
    {
        AuditLog::create([
            'operation_type' => 'test.operation',
            'actor_type'     => 'admin',
            'actor_id'       => $this->admin->id,
            'target_type'    => null,
            'target_id'      => null,
            'outcome'        => 'success',
            'metadata'       => null,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/audit-logs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'current_page',
                'per_page',
                'total',
            ]);

        $this->assertGreaterThan(0, $response->json('total'));
    }

    public function test_admin_can_filter_audit_log_by_operation_type(): void
    {
        AuditLog::create([
            'operation_type' => 'admin.account.created',
            'actor_type'     => 'admin',
            'actor_id'       => $this->admin->id,
            'target_type'    => null,
            'target_id'      => null,
            'outcome'        => 'success',
            'metadata'       => null,
        ]);

        AuditLog::create([
            'operation_type' => 'admin.investment.created',
            'actor_type'     => 'admin',
            'actor_id'       => $this->admin->id,
            'target_type'    => null,
            'target_id'      => null,
            'outcome'        => 'success',
            'metadata'       => null,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/audit-logs?operation_type=admin.account.created');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
        foreach ($data as $log) {
            $this->assertEquals('admin.account.created', $log['operation_type']);
        }
    }

    public function test_admin_can_filter_audit_log_by_date_range(): void
    {
        // Insert directly via DB to set a specific created_at (AuditLog is immutable/append-only)
        \Illuminate\Support\Facades\DB::table('audit_logs')->insert([
            'operation_type' => 'test.date.filter',
            'actor_type'     => 'admin',
            'actor_id'       => $this->admin->id,
            'target_type'    => null,
            'target_id'      => null,
            'outcome'        => 'success',
            'metadata'       => null,
            'created_at'     => '2024-01-15 12:00:00',
        ]);

        // Also insert one outside the range to confirm filtering works
        \Illuminate\Support\Facades\DB::table('audit_logs')->insert([
            'operation_type' => 'test.date.outside',
            'actor_type'     => 'admin',
            'actor_id'       => $this->admin->id,
            'target_type'    => null,
            'target_id'      => null,
            'outcome'        => 'success',
            'metadata'       => null,
            'created_at'     => '2023-06-01 12:00:00',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/audit-logs?date_from=2024-01-01&date_to=2024-01-31');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertNotEmpty($data);

        foreach ($data as $log) {
            $createdAt = \Carbon\Carbon::parse($log['created_at']);
            $this->assertTrue($createdAt->gte(\Carbon\Carbon::parse('2024-01-01')));
            $this->assertTrue($createdAt->lte(\Carbon\Carbon::parse('2024-01-31')->endOfDay()));
        }
    }

    public function test_admin_can_filter_audit_log_by_outcome(): void
    {
        AuditLog::create([
            'operation_type' => 'test.success',
            'actor_type'     => 'admin',
            'actor_id'       => $this->admin->id,
            'target_type'    => null,
            'target_id'      => null,
            'outcome'        => 'success',
            'metadata'       => null,
        ]);

        AuditLog::create([
            'operation_type' => 'test.error',
            'actor_type'     => 'admin',
            'actor_id'       => $this->admin->id,
            'target_type'    => null,
            'target_id'      => null,
            'outcome'        => 'error',
            'metadata'       => null,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/audit-logs?outcome=success');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
        foreach ($data as $log) {
            $this->assertEquals('success', $log['outcome']);
        }
    }

    public function test_non_admin_cannot_access_audit_log(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->getJson('/api/v1/admin/audit-logs');

        $response->assertStatus(403);
    }
}
