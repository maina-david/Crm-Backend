<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EscalationLevel extends Model
{
    protected $fillable = [
        "name",
        "helpdesk_id",
        "form_id",
        "sequence",
        "escalation_point_id",
        "sla",
        "sla_measurement",
        "company_id"
    ];

    /**
     * Get the helpdesk associated with the EscalationLevel
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function helpdesk(): HasOne
    {
        return $this->hasOne(HelpDeskTeam::class, 'id', 'helpdesk_id');
    }

    /**
     * Get the form associated with the EscalationLevel
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function form(): HasOne
    {
        return $this->hasOne(CentralizedForm::class, 'id', 'form_id');
    }
}