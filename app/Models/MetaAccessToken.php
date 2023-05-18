<?php

namespace App\Models;

use App\traits\Encryptable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetaAccessToken extends Model
{
    use Encryptable;

    protected $fillable = [
        'company_id',
        'access_token',
        'active'
    ];

    /* Telling the model to encrypt the name field. */
    protected $encryptable = [
        'access_token',
    ];
}
