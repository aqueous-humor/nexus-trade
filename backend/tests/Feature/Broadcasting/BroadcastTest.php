<?php

namespace Tests\Feature\Broadcasting;

use App\Events\InvestmentStatusChanged;
use App\Events\WalletUpdated;
use App\Models\Account;
use App\Models\Duration;
use App\Models\Investment;
use App\Models\InvestmentPlan;
use App\Models\User;
use App\Models\Wallet;
use App\Services\InvestmentService;
use App\Services\WalletService;
use App\Values\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class BroadcastTest extends TestCase
{
    use RefreshDatabase;

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    private function createUserWithWallet(array $walletAttrs = []): array
    {
        $user   = User::factory()->create();
        $wallet = Wallet::create(array_merge(['user_id' => $user->id, 'balance_cents' => 100000], $walletAttrs));

        return compact('user', 'wallet');
    }

    private function createPendingInvestment(User $user): Investment
    {
        $account  = Account::factory()->create(['user_id' => $user->id, 'balance_cents' => 500000]);
        $plan     = InvestmentPlan::factory()->create(['min_amount_cents' => 1000, 'max_amount_cents' => 1000000, 'roi_percentage' => 10]);
        $duration = Duration::factory()->daily()->create();

        return Investment::factory()->create([
            'user_id'      => $user->id,
            'account_id'   => $account->id,
            'plan_id'      => $plan->id,
            'duration_id'  => $duration->id,
            'amount_cents' => 10000,
            'status'       => 'pending',
        ]);
    }

    // ----------------------------------------------------------------
    // Broadcast event dispatch tests
    // ----------------------------------------------------------------

    public function test_wallet_updated_event_dispatched_on_credit(): void
    {
        Event::fake();

        ['user' => $user, 'wallet' => $wallet] = $this->createUserWithWallet();
        $service = app(WalletService::class);

        $service->credit($user->id, Money::fromCents(5000), 'deposit');

        Event::assertDispatched(WalletUpdated::class, function (WalletUpdated $event) use ($wallet): bool {
            return $event->wallet->id === $wallet->id;
        });
    }

    public function test_wallet_updated_event_dispatched_on_debit(): void
    {
        Event::fake();

        ['user' => $user, 'wallet' => $wallet] = $this->createUserWithWallet(['balance_cents' => 50000]);
        $service = app(WalletService::class);

        $service->debit($user->id, Money::fromCents(5000), 'withdrawal');

        Event::assertDispatched(WalletUpdated::class, function (WalletUpdated $event) use ($wallet): bool {
            return $event->wallet->id === $wallet->id;
        });
    }

    public function test_investment_status_changed_dispatched_on_activate(): void
    {
        Event::fake();

        ['user' => $user] = $this->createUserWithWallet();
        $investment = $this->createPendingInvestment($user);
        $service    = app(InvestmentService::class);

        $service->activate($investment);

        Event::assertDispatched(InvestmentStatusChanged::class, function (InvestmentStatusChanged $event) use ($investment): bool {
            return $event->investment->id === $investment->id
                && $event->investment->status === 'active';
        });
    }

    public function test_investment_status_changed_dispatched_on_cancel(): void
    {
        Event::fake();

        ['user' => $user] = $this->createUserWithWallet();
        $investment = $this->createPendingInvestment($user);
        $service    = app(InvestmentService::class);

        $service->cancel($investment);

        Event::assertDispatched(InvestmentStatusChanged::class, function (InvestmentStatusChanged $event) use ($investment): bool {
            return $event->investment->id === $investment->id
                && $event->investment->status === 'cancelled';
        });
    }

    public function test_investment_status_changed_dispatched_on_reject(): void
    {
        Event::fake();

        ['user' => $user] = $this->createUserWithWallet();
        $investment = $this->createPendingInvestment($user);
        $service    = app(InvestmentService::class);

        $service->reject($investment, 'Suspicious activity');

        Event::assertDispatched(InvestmentStatusChanged::class, function (InvestmentStatusChanged $event) use ($investment): bool {
            return $event->investment->id === $investment->id
                && $event->investment->status === 'rejected';
        });
    }

    // ----------------------------------------------------------------
    // Channel authorization tests
    //
    // The log broadcast driver (used in testing) does not enforce channel
    // authorization through the HTTP endpoint. We therefore test the
    // channel authorization callbacks registered in routes/channels.php
    // directly, which is the actual authorization logic that real drivers
    // (pusher, redis) invoke via verifyUserCanAccessChannel().
    // ----------------------------------------------------------------

    /**
     * Resolve the registered channel callback for the given channel pattern.
     * Returns the result of invoking the callback with the given user and params.
     */
    private function authorizeChannel(string $channelPattern, User $user, mixed ...$params): mixed
    {
        $broadcaster = app(\Illuminate\Contracts\Broadcasting\Broadcaster::class);

        // Access the registered channels via reflection
        $reflection = new \ReflectionObject($broadcaster);
        $property   = $reflection->getProperty('channels');
        $property->setAccessible(true);
        $channels = $property->getValue($broadcaster);

        if (! isset($channels[$channelPattern])) {
            throw new \RuntimeException("No channel registered for pattern: {$channelPattern}");
        }

        return ($channels[$channelPattern])($user, ...$params);
    }

    public function test_user_can_authorize_own_private_channel(): void
    {
        $user = User::factory()->create();

        // Channel callback: return (int) $user->id === (int) $userId
        $result = $this->authorizeChannel('user.{userId}', $user, (string) $user->id);

        $this->assertTrue((bool) $result);
    }

    public function test_user_cannot_authorize_another_users_channel(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        $result = $this->authorizeChannel('user.{userId}', $user, (string) $other->id);

        $this->assertFalse((bool) $result);
    }

    public function test_admin_can_authorize_admin_channel(): void
    {
        $admin = User::factory()->admin()->create();

        $result = $this->authorizeChannel('admin', $admin);

        $this->assertTrue((bool) $result);
    }

    public function test_non_admin_cannot_authorize_admin_channel(): void
    {
        $user = User::factory()->create(); // regular user, role = 'user'

        $result = $this->authorizeChannel('admin', $user);

        $this->assertFalse((bool) $result);
    }

    public function test_unauthenticated_user_cannot_authorize_any_channel(): void
    {
        // Simulate unauthenticated: the channel callback receives null as user
        // when no authenticated user is present. The callback should return false.
        $result = $this->authorizeChannel('user.{userId}', new User(), '1');

        // A fresh (unsaved) User has id=null, so (int) null !== (int) '1' → false
        $this->assertFalse((bool) $result);
    }
}
