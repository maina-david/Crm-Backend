<?php

namespace App\Models;

use App\traits\Encryptable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    use HasFactory, Encryptable;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'maiden_name',
        'last_name',
        'is_primary',
        'account_id',
        'contact_form_id',
        'company_id',
        'account_type_id',
        'approved_by',
        'created_by',
    ];

    /* Telling the model to encrypt the name field. */
    protected $encryptable = [
        'first_name',
        'maiden_name',
        'last_name',
    ];

    /**
     * Get all of the contact data for the Contact
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function contact_data(): HasMany
    {
        return $this->hasMany(ContactData::class);
    }

    /**
     * Get all of the contact_logs for the Contact
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function contact_logs(): HasMany
    {
        return $this->hasMany(ContactLog::class, 'contact_id');
    }

    /**
     * Get all of the tickets for the Contact
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'contact_id');
    }

    /**
     * Get all of the social_chat_accounts for the Contact
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function social_chat_accounts(): HasMany
    {
        return $this->hasMany(ContactSocialAcct::class, 'contact_id');
    }
}
