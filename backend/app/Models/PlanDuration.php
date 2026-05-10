<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PlanDuration extends Pivot
{
    protected $table = 'plan_durations';

    public $timestamps = false;

    protected $fillable = [
        'plan_id',
        'duration_id',
    ];

    // Relationships

    public function investmentPlan(): BelongsTo
    {
        return $this->belongsTo(InvestmentPlan::class, 'plan_id');
    }

    public function duration(): BelongsTo
    {
        return $this->belongsTo(Duration::class);
    }
}
