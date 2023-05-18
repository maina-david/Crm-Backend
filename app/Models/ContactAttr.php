<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactAttr extends Model
{
    use HasFactory;

    protected $fillable=[
        'contact_id',
        'contact_form_item_id',
        'value',
        'opt_value',
    ];
}
