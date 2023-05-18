<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketCreationFormComponent extends Model
{
    protected $fillable = [
        'form_id',
        'name',
        'dataType',
        'selectedOption',
        'multipleOptions',
        'checkBoxOptions',
        'parent_id',
        'nodeId',
        'active'
    ];
}