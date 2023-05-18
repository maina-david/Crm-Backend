<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EscalationLog extends Model
{
    protected $fillable = [
        'ticket_id',
        'changed_by',
        'previous_level',
        'current_level',
        'escalation_point_id',
        'assigned_to',
        'start_time',
        'end_time',
        'status'
    ];
}