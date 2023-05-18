<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel_id',
        'name',
        'description',
        'active'
    ];

    protected $guarded = ['channel_id'];

    protected $hidden = ['channel_id', 'description', 'created_at', 'updated_at'];

    /**
     * Get the channel that owns the SmsProvider
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class, 'channel_id', 'id');
    }
}