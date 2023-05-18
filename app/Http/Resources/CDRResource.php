<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CDRResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $cdr["agent_name"] = $this->agent->name;
        $cdr["call_id"] = $this->call_id;
        $cdr["phone_number"] = $this->phone_number;
        $cdr["desposition"] = $this->desposition;
        $cdr["call_time"] = $this->call_time;
        $cdr["hold_time"] = $this->hold_time;
        $cdr["mute_time"] = $this->mute_time;
        $cdr["queue_time"] = $this->queue_time;
        $cdr["time_to_answer"] = $this->time_to_answer;
        $cdr["queue"] = ($this->queue != null) ? $this->queue->name : "";
        $cdr["call_date"] = $this->created_at;
        $cdr["call_type"] = $this->call_type;
        $cdr["audio_url"] = $this->audio_url;

        return $cdr;

        // return parent::toArray($request);
    }
}