<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Duration extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $fillable = [
        'unit',
        'value',
        'label',
    ];

    // Relationships

    public function investmentPlans(): BelongsToMany
    {
        return $this->belongsToMany(InvestmentPlan::class, 'plan_durations')
            ->using(PlanDuration::class)
            ->withPivot('id');
    }

    public function investments(): HasMany
    {
        return $this->hasMany(Investment::class);
    }
}
