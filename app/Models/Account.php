<?php

namespace App\Models;

use App\traits\Encryptable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Account extends Model
{
    use HasFactory, Encryptable;

    protected $fillable = [
        "account_number",
        "first_name",
        "middle_name",
        "last_name",
        "account_form_id",
        "account_type_id",
        "created_by",
        "updated_by",
        "approved_by",
        "company_id"
    ];

    /* Telling the model to encrypt the name field. */
    protected $encryptable = [
        "first_name",
        "middle_name",
        "last_name",
    ];

    public function account_data()
    {
        return $this->hasMany(AccountData::class);
    }

    public function account_type()
    {
        return $this->belongsTo(AccountType::class, "account_type_id");
    }

    public function account_form()
    {
        return $this->belongsTo(AccountForm::class, "account_form_id");
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * Get all of the account_logs for the Account
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function account_logs(): HasMany
    {
        return $this->hasMany(AccountLog::class, 'account_id');
    }

    /**
     * Get all of the tickets for the Account
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'account_id');
    }

    /**
     * Get all of the social_chat_accounts for the Account
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function social_chat_accounts(): HasMany
    {
        return $this->hasMany(ContactSocialAcct::class, 'account_id');
    }
}
