<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallIvrLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'call_log_id',
        'call_id',
        'currnt_ivr_flow',
        'data',
        'next_ivr_flow',
        'company_id'
    ];

    public function call_log()
    {
        return $this->belongsTo(CallLog::class);
    }
}
