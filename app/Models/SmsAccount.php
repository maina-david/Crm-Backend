<?php

namespace App\Models;

use App\traits\Encryptable;
use App\traits\filterByCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsAccount extends Model
{
    use filterByCompany, Encryptable;

    protected $fillable = [
        'company_id',
        'provider_id',
        'name',
        'description',
        'username',
        'short_code',
        'api_key',
        'api_secret',
        'active'
    ];

    protected $with = ['sms_provider'];

    protected $hidden = ['provider_id', 'api_key', 'api_secret'];

    /* Telling the model to encrypt the name field. */
    protected $encryptable = ['username', 'api_key', 'api_secret'];

    /**
     * Get the company that owns the SmsSetting
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the sms_provider that owns the SmsSetting
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function sms_provider(): BelongsTo
    {
        return $this->belongsTo(SmsProvider::class, 'provider_id', 'id');
    }
}