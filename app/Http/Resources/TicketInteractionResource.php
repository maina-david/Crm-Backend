<?php

namespace App\Http\Resources;

use App\Models\CallLog;
use App\Models\Conversation;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketInteractionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $ticket_interaction = [
            'id' => $this->id,
            'ticket_id' => $this->ticket_id,
            'interaction_reference' => $this->interaction_reference,
            'channel' => $this->channel->name,
            'contact' => $this->contact,
        ];

        if ($this->channel_id == 7) {
            $calls = CallLog::where([
                "call_id" => $this->interaction_reference,
            ])->get();

            if (!empty($calls)) {
                $ticket_interaction['calls'] = CallLogResource::collection($calls);
            }
        } else {
            $conversation = Conversation::find($this->interaction_reference);

            if ($conversation) {
                $ticket_interaction['conversation'] = new ConversationResource($conversation);
            }
        }
        return $ticket_interaction;
    }
}