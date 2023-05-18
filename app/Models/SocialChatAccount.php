<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialChatAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        "account_id",
        "social_chat_id",
        "social_chat_username",
        "channel_id"
    ];
}