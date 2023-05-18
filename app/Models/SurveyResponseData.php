<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyResponseData extends Model
{
    use HasFactory;

    protected $fillable=[
        "survey_id",
        "survey_response_id",
        "survey_form_attr_id",
        "value"
    ];
}
