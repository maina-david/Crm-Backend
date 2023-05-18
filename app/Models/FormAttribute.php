<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormAttribute extends Model
{
    use HasFactory;

    protected $fillable = [
        "name",
        "data_name",
        "is_required",
        "data_type",
        "is_masked",
        "form_id",
        "status",
        "sequence",
        "company_id",
    ];

    protected $with = ['form_attr_options'];
    /**
     * Get all of the form_attr_options for the FormAttribute
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function form_attr_options(): HasMany
    {
        return $this->hasMany(FormAttributeOption::class, 'form_attr_id', 'id');
    }
}