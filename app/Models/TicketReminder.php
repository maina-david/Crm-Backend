<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TicketReminder extends Model
{
    protected $fillable = [
        'ticket_id',
        'user_id',
        'ticket_reminder_type_id',
        'reminder_date'
    ];

    /**
     * Get the user that owns the TicketReminder
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get the ticket that owns the TicketReminder
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id', 'id');
    }

    /**
     * Get the reminderType associated with the TicketReminder
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function reminderType(): HasOne
    {
        return $this->hasOne(TicketReminderType::class, 'ticket_reminder_type_id', 'id');
    }
}