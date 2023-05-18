<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MohFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'company_id',
        'file_url',
        'moh_id',
        'sequence'
    ];
}
