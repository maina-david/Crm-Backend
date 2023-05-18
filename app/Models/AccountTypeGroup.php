<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountTypeGroup extends Model
{
    use HasFactory;
    protected $fillable = [
        'accounttype_id',
        'group_id',
    ];
}
