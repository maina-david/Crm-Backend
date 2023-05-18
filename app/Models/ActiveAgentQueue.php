<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActiveAgentQueue extends Model
{
    use HasFactory;

    protected $fillable = [
        'queue_id',
        'sip_id',
        'user_id',
        'status',
        'is_paused',
        'last_call_hung_up_at',
        'company_id',
        'sip_status',
        'penality'
    ];

    /**
     * Get the agent that owns the ActiveAgentQueue
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
