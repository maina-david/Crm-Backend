<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountFormAttrOption extends Model
{
    use HasFactory;

    protected $fillable=[
        "option_name",
        "account_form_attr_id"
    ];
}
