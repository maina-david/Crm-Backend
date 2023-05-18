<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoiceBroadcastSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        "campaign_id",
        "audio_url",
        "did"
    ];

    /**
     * Get the did_detail that owns the VoiceBroadcastSetting
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function did_detail(): BelongsTo
    {
        return $this->belongsTo(DidList::class, 'did');
    }
}
