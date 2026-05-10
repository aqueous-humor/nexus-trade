<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Signal extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        'provider_metadata',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'provider_metadata' => 'array',
        ];
    }

    // Relationships

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function signalSubscriptions(): HasMany
    {
        return $this->hasMany(SignalSubscription::class);
    }
}
