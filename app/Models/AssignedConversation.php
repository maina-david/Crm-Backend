<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssignedConversation extends Model
{
    protected $fillable = [
        'channel_id',
        'conversation_id',
        'conv_queue_id',
        'agent_id',
        'status',
        'first_response',
        'closed_at',
        'user_notified'
    ];

    protected $hidden = [
        'conv_queue_id',
        'first_response',
        'closed_at',
        'user_notified',
        'created_at',
        'updated_at'
    ];

    /**
     * Get the conversation that owns the AssignedConversation
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id', 'id');
    }
    /**
     * Get the conversation that owns the AssignedConversation
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function conversations(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id', 'id');
    }

    /**
     * Get all of the messages for the AssignedConversation
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class, 'conversation_id', 'conversation_id');
    }
}