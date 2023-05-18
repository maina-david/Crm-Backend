<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $ticketData = [
            'id' => $this->id,
            'ticket_number' => $this->ticket_number,
            'created_by' => $this->createdBy->name,
            'created_on' => $this->created_at,
            'updated_at' => $this->updated_at,
            'status' => $this->status,
            'channel' => $this->channel->name
        ];

        if (isset($this->priority->name)) {
            $ticketData['priority'] = $this->priority->name;
        } else {
            $ticketData['priority'] = 'NOT SET';
        }

        if (isset($this->assignedTo->name)) {
            $ticketData['assigned_to'] = $this->assignedTo->name;
        } else {
            $ticketData['assigned_to'] = 'NOT ASSIGNED';
        }

        return $ticketData;
    }
}