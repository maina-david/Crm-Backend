<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormComponent extends Model
{
    protected $fillable = [
        'name',
        'characteristics',
        'active'
    ];
}