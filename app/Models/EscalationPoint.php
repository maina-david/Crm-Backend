<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EscalationPoint extends Model
{
    /* A list of fields that can be mass assigned. */
    protected $fillable = [
        'company_id',
        'priority_id',
        'name',
        'description',
        'ticket_form_id',
        'escalation_matrix',
        'ui_form'
    ];

    /**
     * Get the company that owns the EscalationPoint
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get all of the escalation_levels for the EscalationPoint
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function escalation_levels(): HasMany
    {
        return $this->hasMany(EscalationLevel::class, 'escalation_point_id');
    }
}