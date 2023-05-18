<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpDeskTeamConfiguration extends Model
{
    protected $fillable = [
        'help_desk_team_id',
        'setting',
        'active'
    ];

    /**
     * Get the helpdeskteam that owns the HelpDeskTeamConfiguration
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function helpdeskteam(): BelongsTo
    {
        return $this->belongsTo(HelpDeskTeam::class, 'help_desk_team_id', 'id');
    }
}