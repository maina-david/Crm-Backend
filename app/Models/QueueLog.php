<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueueLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'call_id',
        'company_id',
        'caller_id',
        'queue_id',
        'bridge_out_id',
        'channel_in_id',
        'bridge_in_id',
        'status',
        'call_type',
        'sip_id',
        'original_position',
        'position',
        'moh_play_id',
        'moh_files',
        'group_id',
        'user_id',
        'call_date',
        'queue_time',
        'call_time',
        'hold_time',
        'mute_time',
        'campaign_id',
        'campaign_contact_id'
    ];

    public function queue(): BelongsTo
    {
        return $this->belongsTo(Queue::class);
    }

    /**
     * Get the agent that owns the QueueLog
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
