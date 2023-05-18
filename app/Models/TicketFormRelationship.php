<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketFormRelationship extends Model
{
    use HasFactory;

    protected $fillable=[
        
        "parent_form_id",
        "child_form_id",
        "ticket_form_option_id",
    ];
}
