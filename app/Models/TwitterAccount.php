<?php

namespace App\Models;

use App\traits\Encryptable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TwitterAccount extends Model
{
    use Encryptable;

    protected $fillable = [
        'company_id',
        'account_id',
        'account_name',
        'account_description',
        'consumer_key',
        'consumer_secret',
        'access_token',
        'access_token_secret',
        'active'
    ];

    /* Telling the model to encrypt the name field. */
    protected $encryptable = [
        'consumer_key',
        'consumer_secret',
        'access_token',
        'access_token_secret'
    ];

    /**
     * Get the company that owns the WhatsappAccount
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
