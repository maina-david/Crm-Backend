<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        "campaign_id", "group_id"
    ];
}
