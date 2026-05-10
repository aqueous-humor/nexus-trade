<?php

namespace App\Events;

use App\Models\Investment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvestmentStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Investment $investment) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("user.{$this->investment->user_id}")];
    }

    public function broadcastAs(): string
    {
        return 'investment.status.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'investment_id' => $this->investment->id,
            'status'        => $this->investment->status,
            'result'        => $this->investment->result,
        ];
    }
}
