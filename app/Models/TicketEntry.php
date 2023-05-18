<?php

namespace App\Models;

use App\traits\Encryptable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketEntry extends Model
{
    use HasFactory, Encryptable;

    protected $fillable = [
        "ticket_entry_id",
        "form_item_id",
        "value"
    ];

    /* Telling the model to encrypt the name field. */
    protected $encryptable = [
        "value"
    ];

    /**
     * Get the ticket form that owns the TicketEntry
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ticket_form(): BelongsTo
    {
        return $this->belongsTo(TicketFormItem::class, 'form_item_id', 'id');
    }
}
