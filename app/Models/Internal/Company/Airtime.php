<?php

namespace App\Models\Internal\Company;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Airtime extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'provider',
        'amount'
    ];
}