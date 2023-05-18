<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QAForm extends Model
{
    use HasFactory;

    protected $fillable = [
        "name",
        "description",
        "company_id"
    ];

    protected $with = ['q_a_form_items'];
    /**
     * Get all of the q_a_form_items for the QAForm
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function q_a_form_items(): HasMany
    {
        return $this->hasMany(QAFormAttr::class, 'q_a_form_id');
    }
}