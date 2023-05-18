<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class CompanyAddress extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'country_code',
        'phone',
        'email',
        'city',
        'office_number',
        'additional_information'
    ];

    protected static $logFillable = true;

    public function country()
    {
        return $this->hasOne(Country::class,"iso");
    }
}
