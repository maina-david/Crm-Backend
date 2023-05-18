<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;

class Company extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'system_id',
        'logo',
        'prefered_language',
        'campaign_recall_interval',
        'active'
    ];
    protected static $logFillable = true;

    public function company_address()
    {
        return $this->hasOne(CompanyAddress::class, 'company_id');
    }

    public function company_contacts()
    {
        return $this->hasMany(CompanyContact::class, 'company_id');
    }

    /**
     * Get all of the whatsappAccount for the Company
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function whatsappAccount(): HasMany
    {
        return $this->hasMany(WhatsappAccount::class);
    }

    /**
     * Get all of the users for the Company
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'company_id', 'id');
    }
}