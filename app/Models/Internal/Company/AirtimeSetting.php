<?php

namespace App\Models\Internal\Company;

use Illuminate\Database\Eloquent\Model;

class AirtimeSetting extends Model
{
    protected $fillable = [
        'company_id',
        'provider',
        'distribution',
        'call_rate',
        'active'
    ];
}