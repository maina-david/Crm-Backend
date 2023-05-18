<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IvrFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'company_id',
        'file_url'
    ];
}
