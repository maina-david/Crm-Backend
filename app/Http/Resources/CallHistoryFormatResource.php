<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CallHistoryFormatResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $call_history["phone"] = $this->phone_number;
        $call_history["status"] = $this->desposition == "ABANDONED" ? "missed" : "completed";
        $call_history["start_time"] = $this->created_at;
        $call_history["duration"] = $this->call_time;
        $call_history["direction"] = $this->call_type == "INBOUND" ? "incoming" : "outgoing";
        if ($call_history["direction"] == "outgoing") {
            $call_history["status"] = $this->desposition == "ABANDONED" ? "canceled" : "completed";
        }
        return $call_history;
    }
}