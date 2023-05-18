<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketFormOption extends Model
{
    use HasFactory;

    protected $fillable=[
        "ticket_form_item_id",
        "option"
    ];
}
