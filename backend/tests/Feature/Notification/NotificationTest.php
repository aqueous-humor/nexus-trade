<?php

namespace Tests\Feature\Notification;

use App\Mail\AccountLockedMail;
use App\Mail\DepositConfirmedMail;
use App\Mail\InvestmentCompletedMail;
use App\Mail\InvestmentCreatedMail;
use App\Models\Account;
use App\Models\Duration;
use App\Models\Investment;
use App\Models\InvestmentPlan;
use App\Models\NotificationPreference;
use App\Models\TermsAcceptance;
use App\Models\TermsVersion;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        Mail::fake();
    }

    // ----------------------------------------------------------------
    // Investment created notification
    // ----------------------------------------------------------------

    public function test_investment_created_notification_is_dispatched(): void
    {
        $user     = User::factory()->create();
        $wallet   = Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);
        $account  = Account::factory()->create(['user_id' => $user->id, 'balance_cents' => 500000]);
        $plan     = InvestmentPlan::factory()->create(['min_amount_cents' => 100, 'max_amount_cents' => 1000000, 'roi_percentage' => 10]);
        $duration = Duration::factory()->daily()->create();
        $terms    = TermsVersion::factory()->create();
        TermsAcceptance::create(['user_id' => $user->id, 'terms_version' => $terms->version, 'accepted_at' => now(), 'ip_address' => '127.0.0.1']);
        NotificationPreference::create(['user_id' => $user->id]);

        $this->actingAs($user)->postJson('/api/v1/investments', [
            'account_id'     => $account->id,
            'plan_id'        => $plan->id,
            'duration_id'    => $duration->id,
            'amount'         => '100.00',
            'terms_accepted' => true,
        ]);

        Mail::assertQueued(InvestmentCreatedMail::class);
    }

    // ----------------------------------------------------------------
    // Investment completed notification
    // ----------------------------------------------------------------

    public function test_investment_completed_notification_is_dispatched(): void
    {
        $user     = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);
        $account  = Account::factory()->create(['user_id' => $user->id]);
        $plan     = InvestmentPlan::factory()->create(['roi_percentage' => 10]);
        $duration = Duration::factory()->daily()->create();
        NotificationPreference::create(['user_id' => $user->id]);

        $investment = Investment::factory()->create([
            'user_id'     => $user->id,
            'account_id'  => $account->id,
            'plan_id'     => $plan->id,
            'duration_id' => $duration->id,
            'status'      => 'active',
        ]);

        app(\App\Services\InvestmentService::class)->complete($investment, 'WIN');

        Mail::assertQueued(InvestmentCompletedMail::class);
    }

    // ----------------------------------------------------------------
    // Deposit confirmed notification
    // ----------------------------------------------------------------

    public function test_deposit_confirmed_notification_is_dispatched(): void
    {
        $user   = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);
        NotificationPreference::create(['user_id' => $user->id]);

        $initResponse = $this->actingAs($user)->postJson('/api/v1/wallet/deposit', [
            'currency' => 'USD',
            'amount'   => '100.00',
        ]);

        $txId = $initResponse->json('data.transaction_id');

        // Wire notification into confirm — call service directly
        $tx = Transaction::find($txId);
        app(NotificationService::class)->depositConfirmed($tx);

        Mail::assertQueued(DepositConfirmedMail::class);
    }

    // ----------------------------------------------------------------
    // Preference opt-out prevents send
    // ----------------------------------------------------------------

    public function test_notification_not_sent_when_preference_disabled(): void
    {
        $user     = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);
        $account  = Account::factory()->create(['user_id' => $user->id, 'balance_cents' => 500000]);
        $plan     = InvestmentPlan::factory()->create(['min_amount_cents' => 100, 'max_amount_cents' => 1000000, 'roi_percentage' => 10]);
        $duration = Duration::factory()->daily()->create();
        $terms    = TermsVersion::factory()->create();
        TermsAcceptance::create(['user_id' => $user->id, 'terms_version' => $terms->version, 'accepted_at' => now(), 'ip_address' => '127.0.0.1']);

        // Disable investment_created notification
        NotificationPreference::create(['user_id' => $user->id, 'investment_created' => false]);

        $this->actingAs($user)->postJson('/api/v1/investments', [
            'account_id'     => $account->id,
            'plan_id'        => $plan->id,
            'duration_id'    => $duration->id,
            'amount'         => '100.00',
            'terms_accepted' => true,
        ]);

        Mail::assertNotQueued(InvestmentCreatedMail::class);
    }

    // ----------------------------------------------------------------
    // Account locked notification
    // ----------------------------------------------------------------

    public function test_account_locked_notification_sent_after_5_failed_attempts(): void
    {
        User::factory()->create([
            'email'    => 'alice@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email'    => 'alice@example.com',
                'password' => 'wrong-password',
            ]);
        }

        Mail::assertQueued(AccountLockedMail::class);
    }

    // ----------------------------------------------------------------
    // Notification preferences endpoints
    // ----------------------------------------------------------------

    public function test_user_can_view_notification_preferences(): void
    {
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);
        NotificationPreference::create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson('/api/v1/notifications/preferences');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => [
                'investment_created',
                'investment_completed',
                'deposit_confirmed',
                'withdrawal_update',
                'account_status_change',
            ]]);
    }

    public function test_user_can_update_notification_preferences(): void
    {
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0]);
        NotificationPreference::create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->patchJson('/api/v1/notifications/preferences', [
            'investment_created' => false,
            'deposit_confirmed'  => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.investment_created', false)
            ->assertJsonPath('data.deposit_confirmed', false)
            ->assertJsonPath('data.investment_completed', true); // unchanged
    }
}
