<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketCreationFormUi extends Model
{
    protected $fillable = [
        'form_id',
        'ui_data'
    ];
}