<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class AccessRight extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'access_name',
        'access_description',
        'company_id'
    ];
    protected static $logFillable = true;

    public function role_profile()
    {
        return $this->hasMany(AccessProfile::class,'access_name','access_name');
    }
}
