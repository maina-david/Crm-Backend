<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CallLog extends Model
{
    use HasFactory;
    protected  $primaryKey = 'call_id';
    protected $keyType = 'string';
    protected $fillable = [
        'call_id',
        'did',
        'call_status',
        'call_type',
        'source',
        'caller_id',
        'company_id',
        'campaign_contact_id',
        'play_id'
    ];

    public function call_ivr_log()
    {
        return $this->hasMany(CallIvrLog::class);
    }

    /**
     * Get all of the queue_logs for the CallLog
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function queue_logs(): HasMany
    {
        return $this->hasMany(QueueLog::class, 'call_id');
    }
}
