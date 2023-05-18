<?php

namespace App\Models\Internal\Company;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'channel',
        'transaction_reference',
        'amount',
        'status'
    ];
}