<?php

namespace App\Models\Internal\Company;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AirtimeUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'did',
        'amount_used'
    ];
}