<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IVRFlow extends Model
{
    use HasFactory;

    protected $table = 'ivr_flows';

    protected $fillable = [
        'flow_name',
        'application_type',
        'application_data',
        'parent_id',
        'ui_node_id',
        'ivr_id'
    ];

    public function ivr_links()
    {
        return $this->hasMany(IVRLink::class);
    }

    public function ivrs()
    {
        return $this->belongsTo(IVR::class, "ivr_id");
    }

    public function delete()
    {
        // delete all related photos 
        $this->ivr_links()->delete();
        // as suggested by Dirk in comment,
        // it's an uglier alternative, but faster
        // Photo::where("user_id", $this->id)->delete()

        // delete the user
        return parent::delete();
    }
}
