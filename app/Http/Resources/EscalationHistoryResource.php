<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EscalationHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $escalation_history =  [
            'ticket_id' => $this->ticket_entry_id,
            'escalation_point' => $this->escalation_point->name,
            'escalation_level' => $this->escalation_level->name,
            'changed_date' => $this->created_at,
            'escation_entry' => $this->ticket_escalation_entries ? EscalationEntryResource::collection($this->ticket_escalation_entries) : ''
        ];

        if (isset($this->userChanged->name)) {
            $escalation_history['changed_by'] = $this->userChanged->name;
        }

        return $escalation_history;
    }
}