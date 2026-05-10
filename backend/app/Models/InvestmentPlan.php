<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvestmentPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'min_amount_cents',
        'max_amount_cents',
        'roi_percentage',
        'profit_min_pct',
        'profit_max_pct',
        'is_trending',
        'trending_image_url',
        'trending_title',
        'trending_description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'min_amount_cents' => 'integer',
            'max_amount_cents' => 'integer',
            'roi_percentage'   => 'decimal:4',
            'profit_min_pct'   => 'decimal:4',
            'profit_max_pct'   => 'decimal:4',
            'is_trending'      => 'boolean',
        ];
    }

    // Relationships

    public function durations(): BelongsToMany
    {
        return $this->belongsToMany(Duration::class, 'plan_durations', 'plan_id', 'duration_id')
            ->using(PlanDuration::class)
            ->withPivot('id');
    }

    public function investments(): HasMany
    {
        return $this->hasMany(Investment::class, 'plan_id');
    }
}
