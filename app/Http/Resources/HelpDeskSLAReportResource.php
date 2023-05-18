<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class HelpDeskSLAReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $helpdesk = [
            'name' => $this->name,
            'total_tickets' => $this->total_tickets,
            'tickets_within_sla' => $this->tickets_within_sla,
            'tickets_outside_sla' => $this->tickets_outside_sla
        ];

        $time_in_seconds = 0;

        $resolution_time = 0;

        foreach ($this->assigned_tickets as $key => $assigned_ticket) {
            if ($assigned_ticket->end_time != NULL) {
                $start = strtotime($assigned_ticket->start_time);
                $end = strtotime($assigned_ticket->end_time);

                $seconds = $end - $start;

                $resolution_time = $time_in_seconds + $seconds;
            }
        }

        $helpdesk['average_resolution_time'] = ($resolution_time > 0) ? round($resolution_time / $this->total_tickets, 0) : 0;

        return $helpdesk;
    }
}