<?php

namespace App\Models;

use App\traits\Encryptable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactData extends Model
{
    use HasFactory, Encryptable;

    protected $fillable = [
        "contact_form_attr_id",
        "value",
        "contact_id"
    ];

    /* Telling the model to encrypt the name field. */
    protected $encryptable = [
        "value",
    ];

    public function contact_form_attr()
    {
        return $this->belongsTo(ContactFormAttr::class, "contact_form_attr_id");
    }
}
