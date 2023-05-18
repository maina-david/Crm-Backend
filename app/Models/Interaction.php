<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Interaction extends Model
{
    protected $fillable = [
        'company_id',
        'channel_id',
        'interaction_reference',
        'interaction_type',
        'reviewed'
    ];

    /**
     * Get the review associated with the Interaction
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function review(): HasOne
    {
        return $this->hasOne(QAInteractionReview::class, 'interaction_id');
    }

    protected $with = ['channel'];

    /**
     * Get the channel associated with the TicketInteraction
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function channel(): HasOne
    {
        return $this->hasOne(Channel::class, 'id', 'channel_id');
    }

    /**
     * When an interaction is deleted, delete the associated review
     */
    public static function boot()
    {
        parent::boot();

        static::deleting(function ($interaction) {
            $interaction->review()->delete();
        });
    }
}