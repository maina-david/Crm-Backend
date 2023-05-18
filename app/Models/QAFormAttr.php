<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QAFormAttr extends Model
{
    use HasFactory;

    /* A list of attributes that can be mass assigned. */
    protected $fillable = [
        "q_a_form_id",
        "question",
        "type",
        "weight",
        "range",
        "is_required"
    ];

}
