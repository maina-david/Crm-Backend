<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AgentActivityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        // return parent::toArray($request);

        return [
            'user_id' => $this->user->id,
            'user' => $this->user->name,
            'date' => $this->date,
            'online_time' => $this->online_time,
            'break_time' => $this->break_time,
            'penality' => $this->penality
        ];
    }
}
