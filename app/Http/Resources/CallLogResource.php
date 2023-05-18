<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CallLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $call_log = [
            'call_id' => $this->call_id,
            'call_status' => $this->call_status,
            'call_date' => $this->created_at,
            'did' => $this->did,
            'caller_id' => $this->caller_id,
        ];
        if ($this->call_status == "ANSWERED") {
            $call_log["call_detail"] = QueueLogResource::collection($this->queue_logs);
        }

        return $call_log;
    }
}