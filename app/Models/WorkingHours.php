<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkingHours extends Model
{
    use HasFactory;

    protected $fillable = [
        "date",
        "start_time",
        "end_time",
        "file_url",
        "company_id"
    ];

    public function file()
    {
        return $this->belongsTo(CallcenterSettingAudioFile::class,"file_url");
    }
}
