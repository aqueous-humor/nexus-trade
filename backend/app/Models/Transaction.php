<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Transaction extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'wallet_id',
        'parent_id',
        'type',
        'status',
        'amount_cents',
        'fee_cents',
        'net_amount_cents',
        'currency',
        'exchange_rate',
        'provider',
        'destination_address',
        'reference',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents'     => 'integer',
            'fee_cents'        => 'integer',
            'net_amount_cents' => 'integer',
            'exchange_rate'    => 'decimal:8',
            'metadata'         => 'array',
        ];
    }

    // Relationships

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function parentTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'parent_id');
    }

    public function childTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'parent_id');
    }

    public function fraudAssessment(): MorphOne
    {
        return $this->morphOne(FraudAssessment::class, 'assessable');
    }
}
