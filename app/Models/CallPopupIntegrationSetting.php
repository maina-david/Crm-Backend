<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallPopupIntegrationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        "company_id",
        "name",
        "url",
        "scope",
        "type"
    ];

    /**
     * Get the queue that owns the CallPopupIntegrationSetting
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function queue(): BelongsTo
    {
        return $this->belongsTo(Queue::class, 'scope');
    }
}