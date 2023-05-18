<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentBreak extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'allowed_per_day',
        'maximum_allowed_time',
        'status',
        'company_id'
    ];
}
