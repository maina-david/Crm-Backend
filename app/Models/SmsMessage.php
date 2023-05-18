<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'sms_account_id',
        'message_id',
        'message',
        'recipient',
        'status',
        'delivery_status'
    ];

    protected $hidden = [
        'company_id',
        'sms_account_id',
        'message_id'
    ];

    /**
     * Get the smsAccount that owns the SmsMessage
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function smsAccount(): BelongsTo
    {
        return $this->belongsTo(SmsAccount::class, 'sms_account_id', 'id');
    }
}