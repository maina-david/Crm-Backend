<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CDRTable extends Model
{
    use HasFactory;

    protected $table = 'cdr_tables';

    /* A list of the columns in the table. */
    protected $fillable = [
        'call_id',
        'phone_number',
        "bridge_id",
        "group_id",
        'call_date',
        'queue_time',
        'call_time',
        "hold_time",
        "mute_time",
        'time_to_answer',
        'desposition',
        'call_type',
        'sip_id',
        "user_id",
        "queue_id",
        "audio_url",
        "company_id",
    ];

    protected $with = ['agent', 'queue'];
    /**
     * Get the agent that owns the CDRTable
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the queue that owns the CDRTable
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function queue(): BelongsTo
    {
        return $this->belongsTo(Queue::class, 'queue_id');
    }

    /**
     * Get the call_log that owns the CDRTable
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function call_log(): BelongsTo
    {
        return $this->belongsTo(CallLog::class, 'call_id');
    }

    // /**
    //  * It takes a query and an array of dates, and returns a query that filters the results to only those
    //  * created between the two dates
    //  * 
    //  * @param query The query builder instance.
    //  * @param array dates An array of two dates. The first date is the start date, the second date is the
    //  * end date.
    //  * 
    //  * @return A query builder instance.
    //  */
    // public function scopeCreatedBetweenDates($query, array $dates)
    // {
    //     $start = ($dates[0] instanceof Carbon) ? $dates[0] : Carbon::parse($dates[0]);
    //     $end   = ($dates[1] instanceof Carbon) ? $dates[1] : Carbon::parse($dates[1]);

    //     return $query->whereBetween('created_at', [
    //         $start->startOfDay(),
    //         $end->endOfDay()
    //     ]);
    // }
}
