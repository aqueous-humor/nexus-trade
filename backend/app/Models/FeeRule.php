<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeRule extends Model
{
    use HasFactory;
    protected $fillable = [
        'provider',
        'transaction_type',
        'fee_type',
        'fee_value',
    ];
}
