<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactSocialAcct extends Model
{
    use HasFactory;

    protected $fillable = [
        "contact_id",
        "account_id",
        "social_account",
        "channel_id"
    ];

    /**
     * Get the account that owns the ContactSocialAcct
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /**
     * Get the contacts that owns the ContactSocialAcct
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function contacts(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    /**
     * Get the channel that owns the ContactSocialAcct
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class, 'channel_id');
    }
}
