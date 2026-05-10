<?php

namespace Tests\Property;

use App\Models\Account;
use App\Models\Investment;
use App\Models\InvestmentPlan;
use App\Models\Duration;
use App\Models\User;
use App\Models\Wallet;
use Eris\Generator;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: forex-broker-platform
 */
class AccountPropertyTest extends TestCase
{
    use RefreshDatabase;
    use TestTrait;

    /**
     * Feature: forex-broker-platform, Property 16: Deactivated accounts reject all new investments.
     *
     * For any account with status suspended or deactivated, any investment
     * creation request SHALL be rejected.
     */
    public function test_p16_deactivated_accounts_reject_investment_creation(): void
    {
        $this->forAll(
            Generator\elements('suspended', 'deactivated')
        )
        ->then(function (string $status) {
            $user    = User::factory()->create();
            Wallet::create(['user_id' => $user->id, 'balance_cents' => 1000000]);
            $account = Account::factory()->create(['user_id' => $user->id, 'status' => $status]);

            $response = $this->actingAs($user)->postJson('/api/v1/investments', [
                'account_id'     => $account->id,
                'plan_id'        => 1,
                'duration_id'    => 1,
                'amount'         => '100.00',
                'terms_accepted' => true,
            ]);

            // Must not be 201 — account is not active
            $this->assertNotEquals(201, $response->status(),
                "Investment creation should be rejected for account with status '{$status}'"
            );
        });
    }

    /**
     * Feature: forex-broker-platform, Property 17: Leverage changes are blocked during active investments.
     *
     * For any account with at least one active investment, any leverage
     * change request SHALL be rejected.
     */
    public function test_p17_leverage_blocked_during_active_investment(): void
    {
        $this->forAll(
            Generator\elements(1, 50, 100, 200, 500, 1000)
        )
        ->then(function (int $newLeverage) {
            $user    = User::factory()->create();
            Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);
            $account = Account::factory()->create(['user_id' => $user->id, 'leverage' => 100]);

            // Create an active investment on this account
            $plan     = InvestmentPlan::factory()->create();
            $duration = Duration::factory()->create();
            Investment::factory()->create([
                'user_id'    => $user->id,
                'account_id' => $account->id,
                'plan_id'    => $plan->id,
                'duration_id'=> $duration->id,
                'status'     => 'active',
            ]);

            $response = $this->actingAs($user)->patchJson("/api/v1/accounts/{$account->id}/leverage", [
                'leverage' => $newLeverage,
            ]);

            $this->assertEquals(422, $response->status(),
                "Leverage change should be blocked when account has active investment"
            );
            $this->assertEquals('LEVERAGE_CHANGE_BLOCKED', $response->json('code'));

            // Leverage must remain unchanged
            $this->assertEquals(100, $account->fresh()->leverage);
        });
    }
}
