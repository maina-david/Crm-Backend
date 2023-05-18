<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatBotAccountPivot extends Model
{
    protected $fillable = [
        'chatbot_id',
        'channel_id',
        'account_id'
    ];

    /**
     * Get the chatbot that owns the ChatBotAccountPivot
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function chatbot(): BelongsTo
    {
        return $this->belongsTo(ChatBot::class, 'chatbot_id', 'id');
    }
}