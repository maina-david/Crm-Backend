<?php

namespace App\Models;

use App\traits\Encryptable;
use App\traits\filterByCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailSetting extends Model
{
    use Encryptable, filterByCompany;

    protected $fillable = [
        'company_id',
        'outgoing_transport',
        'smtp_host',
        'smtp_port',
        'incoming_transport',
        'imap_host',
        'imap_port',
        'encryption',
        'username',
        'password',
        'timeout',
        'auth_mode',
        'active'
    ];

    /* Telling the model to encrypt the name field. */
    protected $encryptable = [
        'password',
    ];

    protected $hiddden = ['company_id', 'active'];

    /**
     * Get the company that owns the EmailSetting
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}