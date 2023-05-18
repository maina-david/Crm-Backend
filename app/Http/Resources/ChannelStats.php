<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ChannelStats extends JsonResource
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
            'channel_id' => $this->id,
            'channel_name' => $this->name,
            'assigned_conversations' => $this->conversations_count,
            'unread_messages' => $this->conversation_messages_count
        ];
    }
}