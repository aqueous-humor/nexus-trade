<?php

namespace App\Services;

use App\Mail\AccountLockedMail;
use App\Mail\AccountReactivatedMail;
use App\Mail\AccountSuspendedMail;
use App\Mail\DepositConfirmedMail;
use App\Mail\InvestmentCompletedMail;
use App\Mail\InvestmentCreatedMail;
use App\Mail\SignalDeactivatedMail;
use App\Mail\WithdrawalApprovedMail;
use App\Mail\WithdrawalRejectedMail;
use App\Models\Account;
use App\Models\Investment;
use App\Models\Signal;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Send a notification if the user's preference allows it.
     */
    public function send(User $user, Mailable $mailable, string $preferenceKey): void
    {
        $prefs = $user->notificationPreference;

        // If no preferences record exists, default to sending
        if ($prefs && isset($prefs->$preferenceKey) && ! $prefs->$preferenceKey) {
            return;
        }

        Mail::to($user->email)->queue($mailable);
    }

    public function investmentCreated(Investment $investment): void
    {
        $this->send($investment->user, new InvestmentCreatedMail($investment), 'investment_created');
    }

    public function investmentCompleted(Investment $investment): void
    {
        $this->send($investment->user, new InvestmentCompletedMail($investment), 'investment_completed');
    }

    public function depositConfirmed(Transaction $transaction): void
    {
        $this->send($transaction->user, new DepositConfirmedMail($transaction), 'deposit_confirmed');
    }

    public function withdrawalApproved(Transaction $transaction): void
    {
        $this->send($transaction->user, new WithdrawalApprovedMail($transaction), 'withdrawal_update');
    }

    public function withdrawalRejected(Transaction $transaction, string $reason): void
    {
        $this->send($transaction->user, new WithdrawalRejectedMail($transaction, $reason), 'withdrawal_update');
    }

    public function accountSuspended(Account $account): void
    {
        $this->send($account->user, new AccountSuspendedMail($account), 'account_status_change');
    }

    public function accountReactivated(Account $account): void
    {
        $this->send($account->user, new AccountReactivatedMail($account), 'account_status_change');
    }

    public function signalDeactivated(Signal $signal, User $user): void
    {
        $this->send($user, new SignalDeactivatedMail($signal), 'account_status_change');
    }

    public function accountLocked(User $user): void
    {
        // Account locked notifications always send regardless of preferences
        Mail::to($user->email)->queue(new AccountLockedMail($user));
    }
}
