<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class AccessProfile extends Model
{
    use HasFactory, LogsActivity;
    protected $fillable = [
        'access_name',
        'role_profile_id',
        'company_id'
    ];

    public function access_rights()
    {
        return $this->hasMany(AccessRight::class, 'access_name', 'access_name');
    }
}
