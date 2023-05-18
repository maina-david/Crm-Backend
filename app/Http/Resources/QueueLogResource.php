<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class QueueLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        // return parent::toArray($request);
        return [
            "call_id" => $this->call_id,
            "desposition" => $this->status,
            "call_date" => $this->created_at,
            "caller_id" => $this->caller_id,
            "queue" => $this->queue->name,
            "agent" => ($this->agent != null) ? $this->agent->name : null,
            "call_duration" => $this->queue_time + $this->call_time + $this->hold_time + $this->mute_time
        ];
    }
}
