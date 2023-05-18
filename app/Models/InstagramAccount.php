<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstagramAccount extends Model
{
    protected $fillable = [
        'company_id',
        'facebook_page_id',
        'account_id',
        'account_name',
        'account_description',
        'status'
    ];

    /**
     * Get the facebook page that owns the InstagramPage
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function faceBookPage(): BelongsTo
    {
        return $this->belongsTo(FaceBookPage::class, 'facebook_page_id', 'id');
    }

    /**
     * Get the company that owns the InstagramPage
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
}