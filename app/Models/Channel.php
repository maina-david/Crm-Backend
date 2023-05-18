<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Channel extends Model
{
    protected $fillable = [
        'name',
        'active'
    ];

    protected $hidden = [
        'active',
        'created_at',
        'updated_at'
    ];

    /**
     * Get all of the conversations for the Channel
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'channel_id', 'id');
    }

    /**
     * Get all of the messages for the Channel
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function conversation_messages(): HasManyThrough
    {
        return $this->hasManyThrough(ConversationMessage::class, Conversation::class);
    }
}