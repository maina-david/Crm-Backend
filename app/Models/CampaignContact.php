<?php

namespace App\Models;

use App\Http\Resources\SureveyResponseResource;
use App\traits\Encryptable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CampaignContact extends Model
{
    use HasFactory, Encryptable;

    protected $fillable = [
        "campaign_id",
        "contact_id",
        "name",
        "phone_number",
        'call_time',
        "status",
        "desposition",
        "trail"
    ];

    /* Telling the model to encrypt the name field. */
    protected $encryptable = [
        'name',
        'phone_number'
    ];

    /**
     * Get the campaign that owns the CampaignContact
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'campaign_id');
    }

    /**
     * Get the survey_response associated with the CampaignContact
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function survey_response(): HasOne
    {
        return $this->hasOne(SurveyResponse::class, 'campaign_contact_id');
    }

    /**
     * Get all of the survey_response_data for the CampaignContact
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function survey_response_data(): HasManyThrough
    {
        // return $this->hasManyThrough(Comment::class, Post::class);
        return $this->hasOneThrough(
            SurveyResponse::class,
            SurveyResponseData::class,
            'survey_response_id',
            'id',
            'id',
        );
    }
}
