<?php

namespace App\Http\Resources;

use App\Models\AssignedConversation;
use App\Models\Conversation;
use App\Models\QueueLog;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class QAReviewDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $review = [
            'id' => $this->id,
            'team' => $this->qa_team->name,
            'channel' => $this->interaction->channel->name,
            'interaction_reference' => $this->interaction->interaction_reference,
        ];

        $interactionReference = $this->interaction->interaction_reference;
        if ($this->interaction->channel_id == 7) {
            $call = QueueLog::where('call_id', $interactionReference)->first();
            if ($call) {
                $review['interaction_time'] = $call->created_at;
                $review['queue'] = $call->queue->name;
                $review['handling_time'] = $call->call_time;

                $review['agent'] = $call->agent->name;
            }
        } else {
            $conversation = Conversation::find($interactionReference);

            if ($conversation) {
                $review['interaction_time'] = $conversation->created_at;
                $review['handling_time'] = (gmdate('H:i:s', $conversation->handling_time));
                $review['queue'] = $conversation->queue->name;
                $review['conversation'] = new ConversationResource($conversation);

                $assignedConversation = AssignedConversation::where('conversation_id', $conversation->id)
                    ->where(function ($conversation) {
                        $conversation->whereNotNull('first_response');
                    })->first();

                if ($assignedConversation) {
                    $review['agent'] = User::find($assignedConversation->agent_id)->name;
                }
            }
        }

        $review['evaluation'] = new QAEvaluationResource($this->evaluation);

        return $review;
    }
}