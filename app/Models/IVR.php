<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IVR extends Model
{
    use HasFactory;

    protected $table = 'ivrs';

    protected $fillable = [
        'name',
        'company_id',
        'description'
    ];

    public function ivr_ui()
    {
        return $this->hasOne(IVR_ui::class);
    }

    public function ivr_flow()
    {
        return $this->hasMany(IVRFlow::class);
    }

    public function dids()
    {
        return $this->hasMany(DidList::class,'ivr_id');
    }
}
