<?php

namespace App\Models;

use App\traits\Encryptable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappAccount extends Model
{
    use Encryptable;

    protected $fillable = [
        'company_id',
        'account_name',
        'description',
        'phone_number',
        'phone_number_id',
        'access_token',
        'active'
    ];

    /* Telling the model to encrypt the name field. */
    protected $encryptable = [
        'phone_number',
        'phone_number_id',
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

    // /**
    //  * Get the Bot associated with the WhatsappAccount
    //  *
    //  * @return \Illuminate\Database\Eloquent\Relations\HasOne
    //  */
    // public function chatBot(): HasOne
    // {
    //     return $this->hasOne(User::class, 'foreign_key', 'local_key');
    // }
}
