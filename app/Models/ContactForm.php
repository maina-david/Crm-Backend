<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactForm extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        'company_id',
    ];

    public function account_types()
    {
        return $this->hasMany(AccountType::class);
    }
}
