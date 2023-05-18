<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IVRLink extends Model
{
    use HasFactory;

    protected $table = 'ivr_links';

    protected $fillable = [
        'ivr_flow_id',
        'selection',
        'next_flow_id',
        'ivr_id'
    ];
}
