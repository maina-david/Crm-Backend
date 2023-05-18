<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallcenterOffMusic extends Model
{
    use HasFactory;

    protected $fillable = ["company_id", "file_id"];

    public function file()
    {
        return $this->belongsTo(CallcenterSettingAudioFile::class, "file_id");
    }
}
