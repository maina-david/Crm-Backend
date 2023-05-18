<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationQueue extends Model
{
    protected $fillable = [
        'conversation_id',
        'chat_queue_id',
        'status',
        'assigned_at'
    ];

    /**
     * Get the queue that owns the ConversationQueue
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function queue(): BelongsTo
    {
        return $this->belongsTo(ChatQueue::class, 'chat_queue_id', 'id');
    }
}