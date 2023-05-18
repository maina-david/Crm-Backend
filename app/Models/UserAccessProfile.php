<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class UserAccessProfile extends Model
{
    use HasFactory, LogsActivity;
    protected  $primaryKey ='user_id';

    protected $fillable = [
        'user_id',
        'access_profile_id',
        'company_id'
    ];

    public function access_profile()
    {
        return $this->belongsTo(AccessProfile::class, 'access_profile_id');
    }

    public function users()
    {
        return $this->belongsTo(User::class,'user_id');
    }
}
