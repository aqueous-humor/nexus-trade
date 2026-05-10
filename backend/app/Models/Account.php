<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'broker_id',
        'type',
        'broker_account_id',
        'balance_cents',
        'leverage',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'balance_cents' => 'integer',
            'status'        => 'string',
        ];
    }

    // Relationships

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function broker(): BelongsTo
    {
        return $this->belongsTo(Broker::class);
    }

    public function investments(): HasMany
    {
        return $this->hasMany(Investment::class);
    }

    public function signalSubscriptions(): HasMany
    {
        return $this->hasMany(SignalSubscription::class);
    }

    public function activeSignalSubscription(): HasOne
    {
        return $this->hasOne(SignalSubscription::class)->whereNull('unsubscribed_at');
    }
}
