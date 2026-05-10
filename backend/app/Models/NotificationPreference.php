<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'investment_created',
        'investment_completed',
        'deposit_confirmed',
        'withdrawal_update',
        'account_status_change',
    ];

    protected function casts(): array
    {
        return [
            'investment_created'    => 'boolean',
            'investment_completed'  => 'boolean',
            'deposit_confirmed'     => 'boolean',
            'withdrawal_update'     => 'boolean',
            'account_status_change' => 'boolean',
        ];
    }

    // Relationships

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
