<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class RoleProfile extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'description',
        'company_id'
    ];

    public function access_right()
    {
        return $this->hasMany(AccessProfile::class,'role_profile_id');
    }

    public function users()
    {
        return $this->hasMany(User::class,'id');
    }
}
