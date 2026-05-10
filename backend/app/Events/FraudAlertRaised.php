<?php

namespace App\Events;

use App\Models\FraudAssessment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FraudAlertRaised implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly FraudAssessment $assessment) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('admin')];
    }

    public function broadcastAs(): string
    {
        return 'fraud.alert.raised';
    }

    public function broadcastWith(): array
    {
        return [
            'assessment_id'    => $this->assessment->id,
            'risk_score'       => $this->assessment->risk_score,
            'assessable_type'  => $this->assessment->assessable_type,
            'assessable_id'    => $this->assessment->assessable_id,
        ];
    }
}
