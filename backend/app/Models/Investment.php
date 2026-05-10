<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Investment extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'account_id',
        'plan_id',
        'duration_id',
        'amount_cents',
        'profit_cents',
        'adjusted_profit_cents',
        'status',
        'result',
        'maturity_at',
        'activated_at',
        'completed_at',
        'terms_version',
        'created_by_admin',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents'          => 'integer',
            'profit_cents'          => 'integer',
            'adjusted_profit_cents' => 'integer',
            'maturity_at'           => 'datetime',
            'activated_at'          => 'datetime',
            'completed_at'          => 'datetime',
            'created_by_admin'      => 'boolean',
        ];
    }

    // Relationships

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(InvestmentPlan::class, 'plan_id');
    }

    public function duration(): BelongsTo
    {
        return $this->belongsTo(Duration::class);
    }

    public function fraudAssessment(): MorphOne
    {
        return $this->morphOne(FraudAssessment::class, 'assessable');
    }
}
