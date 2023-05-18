<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class QAEvaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'qa_team_id',
        'queue_type',
        'queue_id',
        'qa_form_id',
        'agent_id',
        'assessed_by',
        'review_id',
        'assessment_total',
        'handling_time'
    ];

    protected $with = ['q_a_form', 'assessedBy', 'agent'];

    /**
     * Get the form associated with the QAEvaluation
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function q_a_form(): HasOne
    {
        return $this->hasOne(QAForm::class, 'id', 'qa_form_id');
    }
    /**
     * Get the user associated with the QAEvaluation
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function assessedBy(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'assessed_by');
    }

    /**
     * Get the review that owns the QAEvaluation
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function review(): BelongsTo
    {
        return $this->belongsTo(QAInteractionReview::class, 'review_id', 'id');
    }

    /**
     * Get the agent associated with the QAEvaluation
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function agent(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'agent_id');
    }

    /**
     * Get all of the details for the QAEvaluation
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function evaluationDetails(): HasMany
    {
        return $this->hasMany(QAEvaluationDetail::class, 'qa_evaluation_id', 'id');
    }
}