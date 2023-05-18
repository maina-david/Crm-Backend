<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'logged_in_at',
        'logged_out_at',
        'online_time',
        'break_time',
        'sip_status',
        'call_status',
        "penality",
        "current_penality"
    ];

    /**
     * Get the user that owns the AgentStatus
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
