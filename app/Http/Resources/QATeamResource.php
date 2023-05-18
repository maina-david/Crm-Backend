<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class QATeamResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $QA_Team = [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'form' => (isset($this->q_a_form) ? $this->q_a_form->name : ''),
            'no_of_supervisors' => $this->team_supervisors_count,
            'supervisors' => (isset($this->team_supervisors) ? UserResource::collection($this->team_supervisors) : ''),
            'no_of_members' => $this->team_members_count,
            'members' => (isset($this->team_members) ? UserResource::collection($this->team_members) : ''),
            'queues' => (isset($this->queues) ? QATeamQueueResource::collection($this->queues) : '')
        ];

        return $QA_Team;
    }
}