<?php

namespace App\Models\Internal\Company;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Licence extends Model
{
    protected $fillable = [
        'company_id',
        'code',
        'issued_on',
        'expires_on',
        'active'
    ];

    /**
     * When a new licence is created, generate a unique code for it
     */
    protected static function booted(): void
    {
        static::creating(function (Licence $licence) {
            $licence->code = Str::uuid()->toString();
        });
    }
}