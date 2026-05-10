<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Broker extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'platform_type',
        'connection_credentials',
        'default_leverage',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'connection_credentials' => 'encrypted:array',
        ];
    }

    // Relationships

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }
}
