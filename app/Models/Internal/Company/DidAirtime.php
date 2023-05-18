<?php

namespace App\Models\Internal\Company;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DidAirtime extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'did',
        'amount'
    ];
}