<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketReminderType extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'active'
    ];

    /**
     * Get all of the TicketReminder for the TicketReminderType
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ticketReminders(): HasMany
    {
        return $this->hasMany(TicketReminder::class);
    }

    /**
     * Get the company that owns to the TicketReminderType
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
}