<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class QAInteractionReview extends Model
{
    protected $fillable = [
        'company_id',
        'interaction_id',
        'q_a_team_id',
        'agent_id',
        'status'
    ];

    protected $with = ['interaction'];

    /**
     * Get the interaction that owns the QAInteractionReview
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function interaction(): BelongsTo
    {
        return $this->belongsTo(Interaction::class, 'interaction_id', 'id');
    }

    /**
     * Get the qa_team that owns the QAInteractionReview
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function qa_team(): BelongsTo
    {
        return $this->belongsTo(QATeam::class, 'q_a_team_id', 'id');
    }

    /**
     * Get the evaluation associated with the QAInteractionReview
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function evaluation(): HasOne
    {
        return $this->hasOne(QAEvaluation::class, 'review_id', 'id');
    }
}