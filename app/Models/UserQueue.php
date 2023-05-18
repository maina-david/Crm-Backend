<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserQueue extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'queue_id',
        'company_id'
    ];

    public function queue()
    {
        return $this->belongsTo(Queue::class);
    }
}
