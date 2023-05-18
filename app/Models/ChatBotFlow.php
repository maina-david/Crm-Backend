<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatBotFlow extends Model
{
    protected $fillable = [
        'flow_name',
        'application_type',
        'application_data',
        'parent_id',
        'ui_node_id',
        'chatbot_id'
    ];

    public function chatbotLinks()
    {
        return $this->hasMany(ChatBotLink::class, 'chatbot_flow_id', 'id');
    }

    public function chatbots()
    {
        return $this->belongsTo(ChatBot::class, "chatbot_id");
    }

    public function delete()
    {
        $this->chatbotLinks()->delete();
        return parent::delete();
    }
}