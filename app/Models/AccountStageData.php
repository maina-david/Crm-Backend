<?php

namespace App\Models;

use App\traits\Encryptable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountStageData extends Model
{
    use HasFactory, Encryptable;

    protected $fillable = [
        "account_stage_id",
        "account_form_attr_id",
        "value"
    ];

    /* Telling the model to encrypt the name field. */
    protected $encryptable = [
        'value'
    ];

    public function account_form_attrs()
    {
        return $this->belongsTo(AccountFormAttr::class, "account_form_attr_id");
    }
}
