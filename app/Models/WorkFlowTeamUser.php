<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkFlowTeamUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_flow_team_id',
        'user_id',
        'available'
    ];
}