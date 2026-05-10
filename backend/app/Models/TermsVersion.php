<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TermsVersion extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $fillable = [
        'version',
        'content',
        'effective_at',
    ];

    protected function casts(): array
    {
        return [
            'effective_at' => 'datetime',
            'created_at'   => 'datetime',
        ];
    }

    // Relationships

    public function termsAcceptances(): HasMany
    {
        return $this->hasMany(TermsAcceptance::class, 'terms_version', 'version');
    }
}
