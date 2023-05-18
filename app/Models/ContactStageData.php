<?php

namespace App\Models;

use App\traits\Encryptable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactStageData extends Model
{
    use HasFactory, Encryptable;

    protected $fillable = [
        "contact_stage_id",
        "contact_form_attr_id",
        "value"
    ];

    /* Telling the model to encrypt the name field. */
    protected $encryptable = [
        "value"
    ];

    public function contact_form_attr()
    {
        return $this->belongsTo(ContactFormAttr::class, "contact_form_attr_id");
    }
}
