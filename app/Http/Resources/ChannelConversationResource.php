<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ChannelConversationResource extends JsonResource
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
            'conversation_id' => $this->conversation->id,
            'customer_name' => $this->conversation->customer_name,
            'customer_id' => $this->conversation->customer_id,
            'status' => $this->conversation->status,
            'channel_id' => $this->conversation->channel_id,
            'channel_name' => $this->conversation->channel->name,
            'unread_messages' => $this->messages_count,
            'created_at' => $this->conversation->created_at
        ];
    }
}