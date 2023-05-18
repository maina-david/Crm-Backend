<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountForm extends Model
{
    use HasFactory;

    protected $fillable = [
        "name",
        'description',
        'company_id'
    ];

    public function account_form_items()
    {
        return $this->hasMany(AccountFormAttr::class);
    }
}
