<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'customer_name' => $this->customer_name,
            'status' => $this->status,
            'channel' => $this->channel->name,
            'agent' => ($this->agent != null) ? $this->agent->name : "Not assigned",
            'date' => $this->created_at,
            'queue' => (isset($this->queue) ? $this->queue->name : ''),
            'handling_time' => (gmdate('H:i:s', $this->handling_time)),
            'messages' => ConversationMessageResource::collection($this->messages)
        ];
    }
}