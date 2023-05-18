<?php

namespace App\Models;

use App\traits\Encryptable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationMessage extends Model
{
    use Encryptable;

    protected $fillable = [
        'conversation_id',
        'message_id',
        'message',
        'message_type',
        'message_level',
        'attachment',
        'attachment_type',
        'direction',
        'agent_id',
        'status'
    ];

    /* Telling the model to encrypt the name field. */
    protected $encryptable = [
        'attachment', 'message'
    ];

    protected $hidden = [
        'message_id',
        'agent_id'
    ];

    /**
     * Get the conversation that owns the ConversationMessage
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
