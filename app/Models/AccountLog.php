<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountLog extends Model
{
    use HasFactory;

    protected $fillable = [
        "account_id",
        "action",
        "changed_by",
        "approved_by",
        "start_date"
    ];

    public function changedBy()
    {
        return $this->belongsTo(User::class,"changed_by");
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class,"approved_by");
    }

    
}
