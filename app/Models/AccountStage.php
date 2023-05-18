<?php

namespace App\Models;

use App\traits\Encryptable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountStage extends Model
{
    use HasFactory, Encryptable;

    protected $fillable = [
        "account_number",
        "first_name",
        "middle_name",
        "last_name",
        "account_form_id",
        "account_type_id",
        "created_by",
        "updated_by",
        "approved_by",
        "company_id",
        "approval_type",
        "account_id"
    ];

    /* Telling the model to encrypt the name field. */
    protected $encryptable = [
        "first_name",
        "middle_name",
        "last_name",
    ];
}
