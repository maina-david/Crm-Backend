<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketFormUIJson extends Model
{
    use HasFactory;

    protected $fillable = [
        "ticket_form_id",
        "json_ui"
    ];
}
