<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketChannel extends Model
{
    use HasFactory;

    protected $fillable = [
        "ticket_entry_id",
        "interaction_reference",
        "channel_id"
    ];

    /**
     * Get the channel that owns the TicketChannel
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class, 'channel_id', 'id');
    }
}