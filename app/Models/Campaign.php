<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        "name",
        "description",
        "campaign_type_id",
        "company_id",
        "status"
    ];

    public function campaign_working_hour()
    {
        return $this->hasMany(CampaignWorkingHour::class);
    }

    public function campaign_type()
    {
        return $this->belongsTo(CampaignType::class, "campaign_type_id", "name");
    }

    public function groups()
    {
        return $this->hasManyThrough(Group::class, CampaignGroup::class, 'campaign_id', 'id', 'id', 'group_id');
    }

    /**
     * Get the survey_form that owns the Campaign
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function survey_form(): BelongsTo
    {
        return $this->belongsTo(CentralizedForm::class, 'survey_form_id');
    }

    /**
     * Get the sms_setting associated with the Campaign
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function sms_setting(): HasOne
    {
        return $this->hasOne(SmsCampaignSetting::class, 'campaign_id');
    }
}
