<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ConversationMessageResource extends JsonResource
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
            'message' => $this->message,
            'message_type' => $this->message_type,
            'message_level' => $this->message_level,
            'attachment' => $this->attachment,
            'attachment_type' => $this->attachment_type,
            'direction' => $this->direction,
            'status' => $this->status
        ];
    }
}