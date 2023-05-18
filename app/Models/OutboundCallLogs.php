<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OutboundCallLogs extends Model
{
    use HasFactory;

    protected $fillable = [
        "sip_channel",
        "phone_channel",
        "sip_bridge",
        "phone_bridge",
        "sip_id",
        "status",
        "phone_number",
        "source"
    ];
}
