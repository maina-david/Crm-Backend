<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatBotUi extends Model
{
    protected $fillable = [
        'chatbot_id',
        'ui_data'
    ];
}