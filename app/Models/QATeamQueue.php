<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QATeamQueue extends Model
{
    use HasFactory;

    protected $fillable = [
        "team_id",
        "queue_id",
        "queue_type"
    ];
}
