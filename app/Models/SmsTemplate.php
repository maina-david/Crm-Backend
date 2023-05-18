<?php

namespace App\Models;

use App\traits\Encryptable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsTemplate extends Model
{
    use HasFactory, Encryptable;

    protected $fillable = [
        "company_id",
        "name",
        "sms_text"
    ];

    /* Telling the model to encrypt the name field. */
    protected $encryptable = [
        'sms_text'
    ];
}
