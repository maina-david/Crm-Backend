<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentSessionLog extends Model
{
    use HasFactory;

    protected $fillable =  [
        "user_id",
        "attribute_type",
        "break_type",
        "start_time",
        "end_time"
    ];
}
