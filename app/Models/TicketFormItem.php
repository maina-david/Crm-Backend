<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketFormItem extends Model
{
    use HasFactory;

    protected $fillable = [
        "ticket_form_id",
        "lable",
        "place_holder",
        "ui_node_id",
        "lable",
        "ui_node_id",
        "data_type",
        "sequence",
        "parent_id"
    ];

    public function ticket_form_option()
    {
        return $this->hasMany(TicketFormOption::class);
    }
}
