<?php

namespace Tests\Feature\Investment;

use App\Models\Account;
use App\Models\Duration;
use App\Models\Investment;
use App\Models\InvestmentPlan;
use App\Models\TermsAcceptance;
use App\Models\TermsVersion;
use App\Models\User;
use App\Models\Wallet;
use App\Services\InvestmentService;
use App\DTOs\CreateInvestmentDTO;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class InvestmentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
    }

    private function makeUser(): array
    {
        $user    = User::factory()->create();
        $wallet  = Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);
        $account = Account::factory()->create(['user_id' => $user->id, 'balance_cents' => 500000]);
        $plan    = InvestmentPlan::factory()->create([
            'min_amount_cents' => 1000,
            'max_amount_cents' => 1000000,
            'roi_percentage'   => 10,
        ]);
        $duration = Duration::factory()->daily()->create();
        $terms    = TermsVersion::factory()->create();
        TermsAcceptance::create([
            'user_id'       => $user->id,
            'terms_version' => $terms->version,
            'accepted_at'   => now(),
            'ip_address'    => '127.0.0.1',
        ]);

        return compact('user', 'wallet', 'account', 'plan', 'duration', 'terms');
    }

    // ----------------------------------------------------------------
    // Create investment via API
    // ----------------------------------------------------------------

    public function test_user_can_create_investment(): void
    {
        ['user' => $user, 'account' => $account, 'plan' => $plan, 'duration' => $duration] = $this->makeUser();

        $response = $this->actingAs($user)->postJson('/api/v1/investments', [
            'account_id'     => $account->id,
            'plan_id'        => $plan->id,
            'duration_id'    => $duration->id,
            'amount'         => '100.00',
            'terms_accepted' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.amount_cents', 10000);

        // Account balance deducted
        $this->assertEquals(490000, $account->fresh()->balance_cents);
    }

    public function test_investment_creation_fails_with_insufficient_balance(): void
    {
        ['user' => $user, 'account' => $account, 'plan' => $plan, 'duration' => $duration] = $this->makeUser();
        $account->update(['balance_cents' => 100]); // only $1

        $response = $this->actingAs($user)->postJson('/api/v1/investments', [
            'account_id'     => $account->id,
            'plan_id'        => $plan->id,
            'duration_id'    => $duration->id,
            'amount'         => '500.00',
            'terms_accepted' => true,
        ]);

        $response->assertStatus(422);
    }

    public function test_investment_creation_fails_without_terms_acceptance(): void
    {
        $user    = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);
        $account = Account::factory()->create(['user_id' => $user->id, 'balance_cents' => 500000]);
        $plan    = InvestmentPlan::factory()->create(['min_amount_cents' => 1000, 'max_amount_cents' => 1000000, 'roi_percentage' => 10]);
        $duration = Duration::factory()->daily()->create();
        TermsVersion::factory()->create(); // terms exist but not accepted

        $response = $this->actingAs($user)->postJson('/api/v1/investments', [
            'account_id'     => $account->id,
            'plan_id'        => $plan->id,
            'duration_id'    => $duration->id,
            'amount'         => '100.00',
            'terms_accepted' => true,
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('code', 'TERMS_NOT_ACCEPTED');
    }

    public function test_investment_creation_fails_for_suspended_account(): void
    {
        ['user' => $user, 'account' => $account, 'plan' => $plan, 'duration' => $duration] = $this->makeUser();
        $account->update(['status' => 'suspended']);

        $response = $this->actingAs($user)->postJson('/api/v1/investments', [
            'account_id'     => $account->id,
            'plan_id'        => $plan->id,
            'duration_id'    => $duration->id,
            'amount'         => '100.00',
            'terms_accepted' => true,
        ]);

        $response->assertStatus(422);
    }

    // ----------------------------------------------------------------
    // Cancel
    // ----------------------------------------------------------------

    public function test_user_can_cancel_pending_investment(): void
    {
        ['user' => $user, 'account' => $account, 'plan' => $plan, 'duration' => $duration] = $this->makeUser();

        $investment = Investment::factory()->create([
            'user_id'      => $user->id,
            'account_id'   => $account->id,
            'plan_id'      => $plan->id,
            'duration_id'  => $duration->id,
            'amount_cents' => 10000,
            'status'       => 'pending',
        ]);

        $balanceBefore = $account->balance_cents;

        $response = $this->actingAs($user)->postJson("/api/v1/investments/{$investment->id}/cancel");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');

        // Balance refunded
        $this->assertEquals($balanceBefore + 10000, $account->fresh()->balance_cents);
    }

    public function test_user_cannot_cancel_active_investment(): void
    {
        ['user' => $user, 'account' => $account, 'plan' => $plan, 'duration' => $duration] = $this->makeUser();

        $investment = Investment::factory()->create([
            'user_id'     => $user->id,
            'account_id'  => $account->id,
            'plan_id'     => $plan->id,
            'duration_id' => $duration->id,
            'status'      => 'active',
        ]);

        $response = $this->actingAs($user)->postJson("/api/v1/investments/{$investment->id}/cancel");

        $response->assertStatus(422)
            ->assertJsonPath('code', 'INVALID_STATE_TRANSITION');
    }

    // ----------------------------------------------------------------
    // Service-level lifecycle tests
    // ----------------------------------------------------------------

    public function test_service_completes_investment_with_win_result(): void
    {
        ['user' => $user, 'account' => $account, 'plan' => $plan, 'duration' => $duration] = $this->makeUser();
        $service = app(InvestmentService::class);

        $investment = Investment::factory()->create([
            'user_id'      => $user->id,
            'account_id'   => $account->id,
            'plan_id'      => $plan->id,
            'duration_id'  => $duration->id,
            'amount_cents' => 100000,
            'status'       => 'active',
        ]);

        $result = $service->complete($investment, 'WIN');

        $this->assertEquals('completed', $result->status);
        $this->assertEquals('WIN', $result->result);
        // profit = round(100000 * 10 / 100) = 10000
        $this->assertEquals(10000, $result->profit_cents);
    }

    public function test_service_completes_investment_with_loss_result(): void
    {
        ['user' => $user, 'account' => $account, 'plan' => $plan, 'duration' => $duration] = $this->makeUser();
        $service = app(InvestmentService::class);

        $investment = Investment::factory()->create([
            'user_id'     => $user->id,
            'account_id'  => $account->id,
            'plan_id'     => $plan->id,
            'duration_id' => $duration->id,
            'status'      => 'active',
        ]);

        $result = $service->complete($investment, 'LOSS');

        $this->assertEquals('completed', $result->status);
        $this->assertEquals('LOSS', $result->result);
        $this->assertEquals(0, $result->profit_cents);
    }

    public function test_service_completes_investment_with_draw_result(): void
    {
        ['user' => $user, 'account' => $account, 'plan' => $plan, 'duration' => $duration] = $this->makeUser();
        $service = app(InvestmentService::class);

        $investment = Investment::factory()->create([
            'user_id'      => $user->id,
            'account_id'   => $account->id,
            'plan_id'      => $plan->id,
            'duration_id'  => $duration->id,
            'amount_cents' => 50000,
            'status'       => 'active',
        ]);

        $balanceBefore = $account->balance_cents;
        $result        = $service->complete($investment, 'DRAW');

        $this->assertEquals('completed', $result->status);
        $this->assertEquals('DRAW', $result->result);
        // Principal returned on DRAW
        $this->assertEquals($balanceBefore + 50000, $account->fresh()->balance_cents);
    }

    public function test_service_rejects_investment_and_refunds(): void
    {
        ['user' => $user, 'account' => $account, 'plan' => $plan, 'duration' => $duration] = $this->makeUser();
        $service = app(InvestmentService::class);

        $investment = Investment::factory()->create([
            'user_id'      => $user->id,
            'account_id'   => $account->id,
            'plan_id'      => $plan->id,
            'duration_id'  => $duration->id,
            'amount_cents' => 20000,
            'status'       => 'pending',
        ]);

        $balanceBefore = $account->balance_cents;
        $result        = $service->reject($investment, 'Fraud detected');

        $this->assertEquals('rejected', $result->status);
        $this->assertEquals($balanceBefore + 20000, $account->fresh()->balance_cents);
    }

    // ----------------------------------------------------------------
    // Maturity command
    // ----------------------------------------------------------------

    public function test_maturity_command_dispatches_jobs_for_matured_investments(): void
    {
        ['user' => $user, 'account' => $account, 'plan' => $plan, 'duration' => $duration] = $this->makeUser();

        // Create an active investment past maturity
        Investment::factory()->create([
            'user_id'     => $user->id,
            'account_id'  => $account->id,
            'plan_id'     => $plan->id,
            'duration_id' => $duration->id,
            'status'      => 'active',
            'maturity_at' => now()->subHour(),
        ]);

        // Create one not yet matured
        Investment::factory()->create([
            'user_id'     => $user->id,
            'account_id'  => $account->id,
            'plan_id'     => $plan->id,
            'duration_id' => $duration->id,
            'status'      => 'active',
            'maturity_at' => now()->addDay(),
        ]);

        $exitCode = Artisan::call('investments:process-maturity');

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('1', Artisan::output());
    }
}
