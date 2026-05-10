<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SignalSubscription extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $fillable = [
        'account_id',
        'signal_id',
        'subscribed_at',
        'unsubscribed_at',
    ];

    protected function casts(): array
    {
        return [
            'subscribed_at'   => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    // Relationships

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function signal(): BelongsTo
    {
        return $this->belongsTo(Signal::class);
    }
}
