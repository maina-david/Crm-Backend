<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class HelpDeskTeam extends Model
{
    protected $fillable = [
        'team_leader_id',
        'name',
        'description',
        'company_id',
        'active'
    ];

    /**
     * Get the company that owns to the HelpDeskTeam
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    /**
     * The users that belong to the HelpDeskTeam
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'help_desk_team_users', 'help_desk_team_id', 'user_id');
    }

    /**
     * Get the user associated with the HelpDeskTeam
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function teamLeader(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'team_leader_id');
    }

    /**
     * Get all of the assigned tickets for the HelpDeskTeam
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function assigned_tickets(): HasManyThrough
    {
        return $this->hasManyThrough(
            TicketAssignment::class,
            HelpDeskTeamUsers::class,
            'help_desk_team_id',
            'user_id',
            'id',
            'user_id'
        );
    }

    /**
     * Get all of the escalations for the HelpDeskTeam
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function escalations(): HasManyThrough
    {
        return $this->hasManyThrough(
            EscalationLog::class,
            HelpDeskTeamUsers::class,
            'help_desk_team_id',
            'changed_by',
            'id',
            'user_id'
        );
    }
}