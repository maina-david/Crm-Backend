<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsCampaignSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        "campaign_id",
        "sms_account_id",
        "sms_text"
    ];
}
