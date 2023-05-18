<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallcenterSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'max_penality',
        'service_level',
        'status'
    ];
}
