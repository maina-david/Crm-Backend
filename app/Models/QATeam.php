<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class QATeam extends Model
{
    use HasFactory;

    protected $fillable = [
        "name",
        "description",
        "q_a_form_id",
        "company_id"
    ];

    protected $with = ['q_a_form', 'team_supervisors', 'team_members', 'queues'];
    /**
     * Get the supervisor associated with the QATeam
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */

    /**
     * Get the q_a_form that owns the QATeam
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function q_a_form(): BelongsTo
    {
        return $this->belongsTo(QAForm::class, 'q_a_form_id');
    }

    /**
     * Get all of the queues for the QATeam
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function queues(): HasMany
    {
        return $this->hasMany(QATeamQueue::class, 'team_id', 'id');
    }
    /**
     * `->hasManyThrough(User::class, QATeamMember::class);`
     * 
     * The `hasManyThrough` function takes two parameters:
     * 
     * 1. The model you want to get to.
     * 2. The intermediate model
     * 
     * @return HasManyThrough A collection of users that are members of the QA team.
     */
    public function team_members(): HasManyThrough
    {
        return $this->hasManyThrough(
            User::class,
            QATeamMember::class,
            'q_a_team_id',
            'id',
            'id',
            'member_id'
        );
    }

    /**
     * Get all of the team_supervisors for the QATeam
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function team_supervisors(): HasManyThrough
    {
        return $this->hasManyThrough(User::class, QATeamSupervisor::class, 'team_id', 'id', 'id', 'user_id');
    }

    /**
     * Get all of the interaction_reviews for the QATeam
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function interaction_reviews(): HasMany
    {
        return $this->hasMany(QAInteractionReview::class, 'q_a_team_id', 'id');
    }

    /**
     * Get all of the evaluations for the QATeam
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function evaluations(): HasMany
    {
        return $this->hasMany(QAEvaluation::class, 'qa_team_id', 'id');
    }
}