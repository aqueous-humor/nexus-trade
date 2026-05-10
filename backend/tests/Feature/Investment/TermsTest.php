<?php

namespace Tests\Feature\Investment;

use App\Models\Account;
use App\Models\Duration;
use App\Models\InvestmentPlan;
use App\Models\TermsAcceptance;
use App\Models\TermsVersion;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TermsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
    }

    // ----------------------------------------------------------------
    // GET /api/v1/terms/current
    // ----------------------------------------------------------------

    public function test_current_terms_returns_latest_version(): void
    {
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);

        TermsVersion::factory()->create(['version' => 'v1.0', 'effective_at' => now()->subDays(10)]);
        TermsVersion::factory()->create(['version' => 'v2.0', 'effective_at' => now()->subDay()]);

        $response = $this->actingAs($user)->getJson('/api/v1/terms/current');

        $response->assertStatus(200)
            ->assertJsonPath('data.version', 'v2.0');
    }

    public function test_current_terms_shows_accepted_status(): void
    {
        $user  = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);
        $terms = TermsVersion::factory()->create(['version' => 'v1.0']);

        TermsAcceptance::create([
            'user_id'       => $user->id,
            'terms_version' => 'v1.0',
            'accepted_at'   => now(),
            'ip_address'    => '127.0.0.1',
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/terms/current');

        $response->assertStatus(200)
            ->assertJsonPath('data.accepted', true);
    }

    // ----------------------------------------------------------------
    // POST /api/v1/terms/accept
    // ----------------------------------------------------------------

    public function test_user_can_accept_terms(): void
    {
        $user  = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);
        TermsVersion::factory()->create(['version' => 'v1.0']);

        $response = $this->actingAs($user)->postJson('/api/v1/terms/accept', [
            'version' => 'v1.0',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('terms_acceptances', [
            'user_id'       => $user->id,
            'terms_version' => 'v1.0',
        ]);
    }

    public function test_accepting_terms_twice_is_idempotent(): void
    {
        $user  = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);
        TermsVersion::factory()->create(['version' => 'v1.0']);

        $this->actingAs($user)->postJson('/api/v1/terms/accept', ['version' => 'v1.0']);
        $response = $this->actingAs($user)->postJson('/api/v1/terms/accept', ['version' => 'v1.0']);

        $response->assertStatus(200);
        $this->assertDatabaseCount('terms_acceptances', 1);
    }

    public function test_accepting_nonexistent_version_fails(): void
    {
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);

        $response = $this->actingAs($user)->postJson('/api/v1/terms/accept', [
            'version' => 'v99.0',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrorFor('version');
    }

    // ----------------------------------------------------------------
    // New version requires re-acceptance
    // ----------------------------------------------------------------

    public function test_new_terms_version_blocks_investment_until_accepted(): void
    {
        $user     = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);
        $account  = Account::factory()->create(['user_id' => $user->id, 'balance_cents' => 500000]);
        $plan     = InvestmentPlan::factory()->create(['min_amount_cents' => 1000, 'max_amount_cents' => 1000000, 'roi_percentage' => 10]);
        $duration = Duration::factory()->daily()->create();

        // User accepted v1.0
        TermsVersion::factory()->create(['version' => 'v1.0', 'effective_at' => now()->subDays(5)]);
        TermsAcceptance::create(['user_id' => $user->id, 'terms_version' => 'v1.0', 'accepted_at' => now()->subDays(5), 'ip_address' => '127.0.0.1']);

        // Admin publishes v2.0 (now the current version)
        TermsVersion::factory()->create(['version' => 'v2.0', 'effective_at' => now()->subHour()]);

        // Investment should be blocked — v2.0 not accepted
        $response = $this->actingAs($user)->postJson('/api/v1/investments', [
            'account_id'     => $account->id,
            'plan_id'        => $plan->id,
            'duration_id'    => $duration->id,
            'amount'         => '100.00',
            'terms_accepted' => true,
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('code', 'TERMS_NOT_ACCEPTED');

        // Accept v2.0 — investment should now succeed
        $this->actingAs($user)->postJson('/api/v1/terms/accept', ['version' => 'v2.0']);

        $response2 = $this->actingAs($user)->postJson('/api/v1/investments', [
            'account_id'     => $account->id,
            'plan_id'        => $plan->id,
            'duration_id'    => $duration->id,
            'amount'         => '100.00',
            'terms_accepted' => true,
        ]);

        $response2->assertStatus(201);
    }

    // ----------------------------------------------------------------
    // Admin terms management
    // ----------------------------------------------------------------

    public function test_admin_can_create_new_terms_version(): void
    {
        $admin = User::factory()->admin()->create();
        Wallet::create(['user_id' => $admin->id, 'balance_cents' => 0]);

        $response = $this->actingAs($admin)->postJson('/api/v1/admin/terms', [
            'version' => 'v3.0',
            'content' => 'Updated terms and conditions content.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.version', 'v3.0');
    }

    public function test_admin_cannot_create_duplicate_version(): void
    {
        $admin = User::factory()->admin()->create();
        Wallet::create(['user_id' => $admin->id, 'balance_cents' => 0]);
        TermsVersion::factory()->create(['version' => 'v1.0']);

        $response = $this->actingAs($admin)->postJson('/api/v1/admin/terms', [
            'version' => 'v1.0',
            'content' => 'Duplicate.',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrorFor('version');
    }
}
