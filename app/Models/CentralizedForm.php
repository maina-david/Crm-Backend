<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CentralizedForm extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'type',
        'name',
        'description',
        'active'
    ];

    protected $with = ['form_attr'];
    /**
     * Get all of the form_attr for the CentralizedForm
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function form_attr(): HasMany
    {
        return $this->hasMany(FormAttribute::class, 'form_id', 'id');
    }
}