<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TicketInteraction extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'interaction_code',
        'ticket_id',
        'channel_id',
        'interaction_reference',
        'contact',
        'reviewed'
    ];

    protected $with = ['channel'];
    /**
     * Get the channel associated with the TicketInteraction
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function channel(): HasOne
    {
        return $this->hasOne(Channel::class, 'id', 'channel_id');
    }

    /**
     * Get the ticket that owns the TicketInteraction
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id', 'id');
    }
}