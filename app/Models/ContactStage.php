<?php

namespace App\Models;

use App\traits\Encryptable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactStage extends Model
{
    use HasFactory, Encryptable;

    protected $fillable = [
        "first_name",
        "middle_name",
        "last_name",
        "contact_form_id",
        "account_id",
        "created_by",
        "approved_by",
        "company_id",
        'account_type_id',
        "approval_type"
    ];

    /* Telling the model to encrypt the name field. */
    protected $encryptable = [
        "first_name",
        "middle_name",
        "last_name",
    ];
}
