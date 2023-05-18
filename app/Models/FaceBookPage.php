<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FaceBookPage extends Model
{
    protected $fillable = [
        'company_id',
        'page_id',
        'page_name',
        'page_description',
        'page_access_token',
        'active'
    ];

    protected $hidden = [
        'page_access_token',
        'created_at',
        'updated_at'
    ];
}