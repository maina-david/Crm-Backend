<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QATeamMember extends Model
{
    use HasFactory;

    protected $fillable = [
        "q_a_team_id",
        "member_id",
        "is_available"
    ];
}