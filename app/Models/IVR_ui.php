<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IVR_ui extends Model
{
    use HasFactory;

    protected $table = 'ivr_uis';

    protected $fillable = [
        'ivr_id',
        'ui_data'
    ];
}
