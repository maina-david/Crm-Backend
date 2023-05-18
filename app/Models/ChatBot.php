<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatBot extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'description'
    ];

    public function chatbotUi()
    {
        return $this->hasOne(ChatBotUi::class);
    }

    public function chatbotFlow()
    {
        return $this->hasMany(ChatBotFlow::class);
    }

    /**
     * Get all of the chatbot links for the ChatBot
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function  chatbotLinks(): HasMany
    {
        return $this->hasMany(ChatBotLink::class, 'chatbot_id', 'id');
    }
}