<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountFormAttr extends Model
{
    use HasFactory;

    protected $fillable = [
        "name",
        "data_name",
        "is_required",
        "data_type",
        "is_masked",
        "account_form_id",
        "status",
        "sequence",
        "company_id",
    ];

    public function account_form_attr_options()
    {
        return $this->hasMany(AccountFormAttrOption::class);
    }
}
