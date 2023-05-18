<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConversationLog extends Model
{
    protected $fillable = [
        'conversation_id',
        'chat_flow_id',
        'current_flow_id',
        'selection',
    ];
}