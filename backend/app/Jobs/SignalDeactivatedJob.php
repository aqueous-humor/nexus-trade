<?php

namespace App\Jobs;

use App\Models\Signal;
use App\Models\SignalSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SignalDeactivatedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly int $signalId) {}

    public function handle(): void
    {
        $signal = Signal::find($this->signalId);
        if (! $signal) {
            return;
        }

        // Get all active subscriptions for this signal
        $subscriptions = SignalSubscription::where('signal_id', $this->signalId)
            ->whereNull('unsubscribed_at')
            ->with('account.user')
            ->get();

        // Unsubscribe all accounts
        SignalSubscription::where('signal_id', $this->signalId)
            ->whereNull('unsubscribed_at')
            ->update(['unsubscribed_at' => now()]);

        // Notify affected users
        foreach ($subscriptions as $subscription) {
            $user = $subscription->account?->user;
            if ($user) {
                app(\App\Services\NotificationService::class)->signalDeactivated($signal, $user);
            }
        }
    }
}
