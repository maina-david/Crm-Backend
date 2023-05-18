<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DidList extends Model
{
    use HasFactory;

    protected $fillable = [
        'did',
        'allocation_status',
        'company_id',
        'carrier_id',
        'ivr_id'
    ];

    public function ivr()
    {
        return $this->belongsTo(IVR::class, 'ivr_id');
    }

    public function carrier()
    {
        return $this->belongsTo(Carrier::class, 'carrier_id');
    }
}
