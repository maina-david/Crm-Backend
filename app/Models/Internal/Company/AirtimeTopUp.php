<?php

namespace App\Models\Internal\Company;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AirtimeTopUp extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'provider',
        'prev_balance',
        'amount',
        'current_balance'
    ];
}