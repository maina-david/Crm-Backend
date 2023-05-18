<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        "campaign_id",
        "campaign_contact_id"
    ];

    /**
     * Get the camapign that owns the SurveyResponse
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function camapign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'campaign_id');
    }

    /**
     * Get the campaign_contact that owns the SurveyResponse
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function campaign_contact(): BelongsTo
    {
        return $this->belongsTo(CampaignContact::class, 'campaign_contact_id');
    }
}
