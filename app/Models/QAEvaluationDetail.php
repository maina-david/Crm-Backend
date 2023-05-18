<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QAEvaluationDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        "qa_evaluation_id",
        "form_item_id",
        "score",
        "result",
        "comment",
        "is_mandatory"
    ];


    /**
     * Get the form_item that owns the QAEvaluationDetail
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function form_item(): BelongsTo
    {
        return $this->belongsTo(QAFormAttr::class, 'form_item_id', 'id');
    }
}