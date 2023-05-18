<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountNumber extends Model
{
    use HasFactory;

    protected $fillable = [
        "name",
        "prefix",
        "has_number",
        "has_character",
        "separator",
        "company_id",
    ];

    public function account_types()
    {
        return $this->hasMany(AccountType::class);
    }
}
