<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
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
            'conversation_id' => $this->conversation_id,
            'message_id' => $this->id,
            'message' => $this->message,
            'attachment' => $this->attachment,
            'attachment_type' => $this->attachment_type,
            'direction' => $this->direction,
            'status' => $this->status,
            'created_at' => $this->created_at
        ];
    }
}