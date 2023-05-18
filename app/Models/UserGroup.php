<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'group_id',
        'company_id'
    ];

    public function groups()
    {
        return $this->hasMany(Group::class,"id","group_id");
    }
}
