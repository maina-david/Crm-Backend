<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutoReplyMessage extends Model
{
    protected $fillable = [
        'chat_queue_id',
        'autoreply_message'
    ];
}