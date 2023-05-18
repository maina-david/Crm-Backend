<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatBotFile extends Model
{
    protected $fillable = [
        'name',
        'company_id',
        'file_type',
        'file_url'
    ];
}