<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class HelpDeskDataAnalyticsResource extends JsonResource
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
            'name' => $this->name,
            'total_tickets' => $this->assigned_tickets_count,
            'escalated_tickets' => $this->escalated_tickets,
            'resolved_tickets' => $this->resolved_tickets
        ];
    }
}