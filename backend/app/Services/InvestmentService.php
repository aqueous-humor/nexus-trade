<?php

namespace App\Services;

use App\Contracts\InvestmentServiceInterface;
use App\DTOs\CreateInvestmentDTO;
use App\Events\InvestmentStatusChanged;
use App\Exceptions\InvalidStateTransitionException;
use App\Exceptions\TermsNotAcceptedException;
use App\Models\Account;
use App\Models\Duration;
use App\Models\Investment;
use App\Models\InvestmentPlan;
use App\Models\TermsAcceptance;
use App\Models\TermsVersion;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvestmentService implements InvestmentServiceInterface
{
    // Valid state transitions
    private const TRANSITIONS = [
        'pending'  => ['active', 'cancelled', 'rejected'],
        'active'   => ['completed'],
        'completed'=> [],
        'cancelled'=> [],
        'rejected' => [],
    ];

    public function create(User $user, Account $account, CreateInvestmentDTO $dto): Investment
    {
        // 1. Account must be active
        if ($account->status !== 'active') {
            throw ValidationException::withMessages([
                'account_id' => ['The selected account is not active.'],
            ]);
        }

        // 2. Terms must be accepted
        $currentTerms = TermsVersion::orderByDesc('effective_at')->first();
        if ($currentTerms) {
            $accepted = TermsAcceptance::where('user_id', $user->id)
                ->where('terms_version', $currentTerms->version)
                ->exists();
            if (! $accepted) {
                throw new TermsNotAcceptedException(
                    'You must accept the current terms before investing.'
                );
            }
        }

        // 3. Load plan and validate amount
        $plan = InvestmentPlan::findOrFail($dto->planId);
        if ($plan->status !== 'active') {
            throw ValidationException::withMessages([
                'plan_id' => ['The selected investment plan is not available.'],
            ]);
        }
        if ($dto->amountCents < $plan->min_amount_cents || $dto->amountCents > $plan->max_amount_cents) {
            throw ValidationException::withMessages([
                'amount' => [
                    "Amount must be between {$plan->min_amount_cents} and {$plan->max_amount_cents} cents.",
                ],
            ]);
        }

        // 4. Load duration and compute maturity
        $duration   = Duration::findOrFail($dto->durationId);
        $maturityAt = $this->computeMaturity($duration);

        return DB::transaction(function () use ($user, $account, $dto, $plan, $duration, $maturityAt, $currentTerms): Investment {
            // Debit account balance
            if ($account->balance_cents < $dto->amountCents) {
                throw ValidationException::withMessages([
                    'amount' => ['Insufficient account balance.'],
                ]);
            }
            $account->decrement('balance_cents', $dto->amountCents);

            $investment = Investment::create([
                'user_id'       => $user->id,
                'account_id'    => $account->id,
                'plan_id'       => $plan->id,
                'duration_id'   => $duration->id,
                'amount_cents'  => $dto->amountCents,
                'profit_cents'  => 0,
                'status'        => 'pending',
                'maturity_at'   => $maturityAt,
                'terms_version' => $currentTerms?->version ?? $dto->termsVersion,
            ]);

            // Dispatch notification
            app(\App\Services\NotificationService::class)->investmentCreated($investment);

            return $investment;
        });
    }

    public function activate(Investment $investment): Investment
    {
        $this->assertTransition($investment, 'active');

        $investment->update([
            'status'       => 'active',
            'activated_at' => now(),
        ]);

        event(new InvestmentStatusChanged($investment));

        return $investment->fresh();
    }

    public function complete(Investment $investment, string $result): Investment
    {
        $this->assertTransition($investment, 'completed');

        if (! in_array($result, ['WIN', 'LOSS', 'DRAW'], true)) {
            throw new \InvalidArgumentException("Invalid result: {$result}");
        }

        $plan        = $investment->plan;
        $profitCents = $this->calculateProfit($investment->amount_cents, $plan->roi_percentage, $result);

        DB::transaction(function () use ($investment, $result, $profitCents): void {
            $investment->update([
                'status'       => 'completed',
                'result'       => $result,
                'profit_cents' => $profitCents,
                'completed_at' => now(),
            ]);

            // Credit profit to account balance on WIN
            if ($result === 'WIN' && $profitCents > 0) {
                $investment->account->increment('balance_cents', $investment->amount_cents + $profitCents);

                // Record profit transaction against wallet
                $wallet = $investment->user->wallet;
                if ($wallet) {
                    Transaction::create([
                        'user_id'          => $investment->user_id,
                        'wallet_id'        => $wallet->id,
                        'type'             => 'profit',
                        'status'           => 'completed',
                        'amount_cents'     => $profitCents,
                        'fee_cents'        => 0,
                        'net_amount_cents' => $profitCents,
                        'metadata'         => ['investment_id' => $investment->id],
                    ]);
                }
            } elseif ($result === 'DRAW') {
                // Return principal on DRAW
                $investment->account->increment('balance_cents', $investment->amount_cents);
            }
            // LOSS: no return, no profit transaction
        });

        event(new InvestmentStatusChanged($investment->fresh()));

        app(\App\Services\NotificationService::class)->investmentCompleted($investment->fresh());

        return $investment->fresh();
    }

    public function cancel(Investment $investment): Investment
    {
        $this->assertTransition($investment, 'cancelled');

        DB::transaction(function () use ($investment): void {
            $investment->update(['status' => 'cancelled']);

            // Refund principal to account
            $investment->account->increment('balance_cents', $investment->amount_cents);

            // Record cancellation transaction
            $wallet = $investment->user->wallet;
            if ($wallet) {
                Transaction::create([
                    'user_id'          => $investment->user_id,
                    'wallet_id'        => $wallet->id,
                    'type'             => 'cancellation',
                    'status'           => 'completed',
                    'amount_cents'     => $investment->amount_cents,
                    'fee_cents'        => 0,
                    'net_amount_cents' => $investment->amount_cents,
                    'metadata'         => ['investment_id' => $investment->id],
                ]);
            }
        });

        event(new InvestmentStatusChanged($investment->fresh()));

        return $investment->fresh();
    }

    public function reject(Investment $investment, string $reason): Investment
    {
        $this->assertTransition($investment, 'rejected');

        DB::transaction(function () use ($investment, $reason): void {
            $investment->update(['status' => 'rejected']);

            // Refund principal to account
            $investment->account->increment('balance_cents', $investment->amount_cents);

            // Record refund transaction
            $wallet = $investment->user->wallet;
            if ($wallet) {
                Transaction::create([
                    'user_id'          => $investment->user_id,
                    'wallet_id'        => $wallet->id,
                    'type'             => 'refund',
                    'status'           => 'completed',
                    'amount_cents'     => $investment->amount_cents,
                    'fee_cents'        => 0,
                    'net_amount_cents' => $investment->amount_cents,
                    'metadata'         => ['investment_id' => $investment->id, 'reason' => $reason],
                ]);
            }
        });

        event(new InvestmentStatusChanged($investment->fresh()));

        return $investment->fresh();
    }

    public function recover(Investment $investment): Investment
    {
        // Retry: if stuck in active past maturity, complete it
        if ($investment->status === 'active' && $investment->maturity_at->isPast()) {
            return $this->complete($investment, 'WIN');
        }

        throw new \RuntimeException(
            "Cannot recover investment #{$investment->id} with status '{$investment->status}'"
        );
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    public static function calculateProfit(int $amountCents, float|string $roiPercentage, string $result): int
    {
        return match ($result) {
            'WIN'  => (int) round($amountCents * (float) $roiPercentage / 100),
            default => 0,
        };
    }

    private function computeMaturity(Duration $duration): Carbon
    {
        return match ($duration->unit) {
            'hour'  => now()->addHours($duration->value),
            'day'   => now()->addDays($duration->value),
            'week'  => now()->addWeeks($duration->value),
            'month' => now()->addMonths($duration->value),
        };
    }

    private function assertTransition(Investment $investment, string $to): void
    {
        $from    = $investment->status;
        $allowed = self::TRANSITIONS[$from] ?? [];

        if (! in_array($to, $allowed, true)) {
            throw new InvalidStateTransitionException($from, $to);
        }
    }
}
