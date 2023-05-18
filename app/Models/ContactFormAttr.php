<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactFormAttr extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'data_name',
        'is_required',
        'data_type',
        'is_masked',
        'contact_form_id',
        'status',
        'sequence',
        'company_id',
    ];

    public function contact_form_attr_opts()
    {
        return $this->hasMany(ContactFormAttrOpts::class,"account_form_attr_id");
    }
}
