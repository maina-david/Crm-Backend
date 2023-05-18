<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ChatQueue extends Model
{
    protected $fillable = [
        'company_id',
        'group_id',
        'name',
        'description',
        'timeout',
        'max_open_conversation',
        'active'
    ];

    /**
     * Get the company that owns the ChatQueue
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the group that owns the ChatQueue
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get all of the users for the ChatQueue
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_queue_users');
    }

    /**
     * Get the autoreplyMessage associated with the ChatQueue
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function autoreplyMessage(): HasOne
    {
        return $this->hasOne(AutoReplyMessage::class);
    }

    /**
     * Get all of the conversations for the ChatQueue
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function conversations(): HasManyThrough
    {
        return $this->hasManyThrough(
            Conversation::class,
            ConversationQueue::class,
            'chat_queue_id',
            'id',
            'id',
            'conversation_id'
        );
    }
}