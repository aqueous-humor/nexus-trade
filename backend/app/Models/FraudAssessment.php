<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FraudAssessment extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $fillable = [
        'assessable_type',
        'assessable_id',
        'risk_score',
        'triggered_rules',
        'reviewed_by',
        'review_decision',
        'review_reason',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'triggered_rules' => 'array',
            'risk_score'      => 'integer',
            'reviewed_at'     => 'datetime',
            'created_at'      => 'datetime',
        ];
    }

    // Relationships

    public function assessable(): MorphTo
    {
        return $this->morphTo();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
