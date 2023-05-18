<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatBotLink extends Model
{
    protected $fillable = [
        'chatbot_flow_id',
        'selection',
        'next_flow_id',
        'chatbot_id'
    ];
}