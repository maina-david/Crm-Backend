<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallTransferLog extends Model
{
    use HasFactory;

    /* Telling the model which fields are allowed to be mass assigned. */
    protected $fillable = [
        "agent_channel",
        "phone_channel",
        "forwarded_channel",
        "original_bridge",
        "transfer_bridge",
        "transfered_by",
        "transfered_to",
        "queue_id"
    ];

    /**
     * Get the user_transfered_to that owns the CallTransferLog
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user_transfered_to(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transfered_to');
    }

    /**
     * Get the user_transfered_by that owns the CallTransferLog
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user_transfered_by(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transfered_by');
    }
}
