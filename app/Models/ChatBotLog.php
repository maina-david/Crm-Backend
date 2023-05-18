<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatBotLog extends Model
{
    protected $fillable = [
        'conversation_id',
        'chat_flow_id',
        'current_flow_id',
        'selection',
    ];

    /**
     * Get the conversation that owns the ChatBotLog
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}