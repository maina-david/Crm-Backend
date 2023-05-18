<?php

namespace App\Models;

use App\traits\Encryptable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountData extends Model
{
    use HasFactory, Encryptable;

    protected $fillable = [
        "account_id",
        "account_form_attr_id",
        "value"
    ];

    /* Telling the model to encrypt the name field. */
    protected $encryptable = [
        'value'
    ];

    public function account_form_attr()
    {
        return $this->belongsTo(AccountFormAttr::class, "account_form_attr_id");
    }

    /**
     * Get all of the acct_form_attr_options for the AccountData
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function acct_form_attr_options(): HasMany
    {
        return $this->hasMany(AccountFormAttrOption::class, 'value', 'local_key');
    }
}
