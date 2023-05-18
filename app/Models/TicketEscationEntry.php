<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketEscationEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        "ticket_escation_id",
        "escation_form_item_id",
        "value"
    ];

    /**
     * Get the form_attribute that owns the TicketEscationEntry
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function form_attribute(): BelongsTo
    {
        return $this->belongsTo(FormAttribute::class, 'escation_form_item_id');
    }
}