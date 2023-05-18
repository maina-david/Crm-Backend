<?php

namespace App\Http\Resources;

use App\Models\CallLog;
use App\Models\Conversation;
use App\Models\QAForm;
use Illuminate\Http\Resources\Json\JsonResource;

class QAInteractionDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $interaction = [
            'id' => $this->id,
            'interaction_reference' => $this->interaction_reference,
            'channel' => $this->channel->name,
            'form' => ($this->review->qa_team->q_a_form_id ? QAForm::with('q_a_form_items')->find($this->review->qa_team->q_a_form_id) : '')
        ];

        if ($this->channel_id == 7) {
            $calls = CallLog::where([
                "call_id" => $this->interaction_reference,
            ])->get();

            if (!empty($calls)) {
                $interaction['calls'] = CallLogResource::collection($calls);
            }
        } else {
            $conversation = Conversation::find($this->interaction_reference);

            if ($conversation) {
                $interaction['conversation'] = new ConversationResource($conversation);
            }
        }
        return $interaction;;
    }
}