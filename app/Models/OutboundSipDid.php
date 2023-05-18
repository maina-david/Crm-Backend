<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutboundSipDid extends Model
{
    use HasFactory;

    protected $fillable = [
        "campany_id",
        "sip_id",
        "did_id"
    ];

    /**
     * Get the sip that owns the OutboundSipDid
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function sip(): BelongsTo
    {
        return $this->belongsTo(SipList::class, 'sip_id');
    }

    /**
     * Get the did that owns the OutboundSipDid
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function did(): BelongsTo
    {
        return $this->belongsTo(DidList::class, 'did_id');
    }
}