<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Invitation extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'email',
        'company_id',
        'group_id',
        'role_profile_id',
        'invited_by',
        'status',
        'accepted_by'
    ];

    public function invited_by()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function role()
    {
        return $this->belongsTo(RoleProfile::class, 'role_profile_id');
    }
}
