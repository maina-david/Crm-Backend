<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketEscalation extends Model
{
    protected $fillable = [
        'ticket_entry_id',
        'escalation_form_id',
        'escalation_point_id',
        'escalation_level_id',
        'changed_by',
        'sla_status'
    ];

    /**
     * Get all of the ticket_escalation_entries for the TicketEscalation
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ticket_escalation_entries(): HasMany
    {
        return $this->hasMany(TicketEscationEntry::class, 'ticket_escation_id', 'id');
    }

    /**
     * Get the escalation_point that owns the TicketEscalation
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function escalation_point(): BelongsTo
    {
        return $this->belongsTo(EscalationPoint::class, 'escalation_point_id');
    }

    /**
     * Get the escalation_level that owns the TicketEscalation
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function escalation_level(): BelongsTo
    {
        return $this->belongsTo(EscalationLevel::class, 'escalation_level_id');
    }

    /**
     * Get the userChanged that owns the TicketEscalation
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function userChanged(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Get all of the tickets for the TicketEscalation
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'id', 'ticket_entry_id');
    }
}