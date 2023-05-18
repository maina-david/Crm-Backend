<?php

namespace App\Models;

use App\traits\filterByCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    use HasFactory, filterByCompany;

    protected $fillable = [
        'company_id',
        'type',
        'name',
        'body',
        'active'
    ];
}