<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Controllers\account\AccounttypeController;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'status',
        'account_form_id',
        'contact_form_id',
        'company_id',
        'account_number_id'
    ];

    public function account_form()
    {
        return $this->belongsTo(AccountForm::class, "account_form_id");
    }

    public function contact_form()
    {
        return $this->belongsTo(ContactForm::class, "contact_form_id");
    }

    public function groups()
    {
        return $this->hasManyThrough(Group::class, AccountTypeGroup::class, "accounttype_id", 'id', 'id', 'group_id');
    }

    public function account_number()
    {
        return $this->belongsTo(AccountNumber::class, "account_number_id");
    }

    /**
     * Get all of the accounts for the AccountType
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class, 'account_type_id');
    }

    /**
     * Get all of the contacts for the AccountType
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class, 'account_type_id');
    }
}
