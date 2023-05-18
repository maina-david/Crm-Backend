<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallcenterHoliday extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'name',
        'description',
        'file_id',
        'company_id',
    ];

    public function file()
    {
        return $this->belongsTo(CallcenterSettingAudioFile::class,"file_id");
    }
}
