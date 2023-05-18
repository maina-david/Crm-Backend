<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TicketCreationForm extends Model
{
    protected $fillable = [
        'company_id',
        'account_id',
        'name',
        'description',
        'priority_id',
        'active'
    ];

    /**
     * Get the formComponents associated with the TicketCreationForm
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function formComponents(): HasOne
    {
        return $this->hasOne(TicketCreationFormComponent::class, 'form_id', 'id');
    }
}