<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EscalationFormData extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'escalation_level_id',
        'helpdesk_id',
        'escalation_point_id',
        'user_id',
        'form_id',
        'form_item_id',
        'form_item_value'
    ];
}