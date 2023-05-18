<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallServer extends Model
{
    use HasFactory;

    protected $fillable = [
        "server_name",
        "ip_address",
        "port",
        "type"
    ];
}
