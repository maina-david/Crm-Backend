<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = [
        'company_id',
        'phone_number_id',
        'customer_id',
        'channel_id',
        'customer_name',
        'subject',
        'status',
        'assigned_to',
        'closed_at',
        'handling_time',
    ];

    protected $hidden = [
        'company_id',
        'phone_number_id',
        'customer_id',
        'channel_id',
        'assigned_to',
        'handling_time'
    ];

    /**
     * Get all of the messages for the Conversation
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class, 'conversation_id', 'id');
    }

    /**
     * Get the user that owns the Conversation
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class, 'channel_id', 'id');
    }

    /**
     * Get the agent that owns the Conversation
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get all of the conversationQueues for the Conversation
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function conversationQueues(): HasMany
    {
        return $this->hasMany(ConversationQueue::class, 'conversation_id', 'id');
    }

    /**
     * Get all of the assignedConversations for the Conversation
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function assignedConversations(): HasMany
    {
        return $this->hasMany(AssignedConversation::class, 'conversation_id', 'id');
    }

    /**
     * This conversation has one chat queue through the conversation queue.
     * 
     * @return The queue of the conversation.
     */
    public function queue()
    {
        return $this->hasOneThrough(
            ChatQueue::class,
            ConversationQueue::class,
            'conversation_id',
            'id',
            'id',
            'chat_queue_id'
        );
    }
}