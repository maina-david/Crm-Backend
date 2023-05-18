<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Queue extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'company_id',
        'group_id',
        'moh_id',
        'wrap_up_time',
        'time_out',
        'join_empty',
        'leave_when_empty',
        'status',
    ];

    public function group()
    {
        return $this->belongsTo(Group::class, "group_id");
    }

    public function moh()
    {
        return $this->belongsTo(MusicOnHold::class, "moh_id");
    }

    public function agents()
    {
        return $this->hasManyThrough(User::class, UserQueue::class, "queue_id", "id", "id", "user_id");
    }

    public function queue_logs()
    {
        return $this->hasMany(QueueLog::class);
    }
}
