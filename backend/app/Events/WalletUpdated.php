<?php

namespace App\Events;

use App\Models\Wallet;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WalletUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Wallet $wallet) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("user.{$this->wallet->user_id}")];
    }

    public function broadcastAs(): string
    {
        return 'wallet.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'balance_cents' => $this->wallet->balance_cents,
            'user_id'       => $this->wallet->user_id,
        ];
    }
}
