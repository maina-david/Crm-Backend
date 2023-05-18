<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallAtribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'call_id',
        'sip_id',
        'attribute_name',
        'start_time',
        'end_time',
    ];
}
