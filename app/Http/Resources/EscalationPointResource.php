<?php

namespace App\Http\Resources;

use App\Models\TicketFormItem;
use App\Models\TicketFormOption;
use Illuminate\Http\Resources\Json\JsonResource;

class EscalationPointResource extends JsonResource
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
        $formatted_escalation = array();
        if (!empty(json_decode($this->escalation_matrix)) > 0) {
            foreach (json_decode($this->escalation_matrix) as $keys => $ui) {
                $form_lable = TicketFormItem::find($keys);
                $formatted_escalation[$form_lable->lable] = TicketFormOption::find($ui)->option;
            }
        }
        return [
            "id" => $this->id,
            "company_id" => $this->comapny_id,
            "priority_id" => $this->priority_id,
            "ticket_form_id" => $this->ticket_form_id,
            "name" => $this->name,
            "description" => $this->description,
            "escalation_matrix" => $this->escalation_matrix,
            "ui_form" => $this->ui_form,
            "escalation_levels_count" => $this->escalation_levels_count,
            "formatted_escalation" => $formatted_escalation
        ];
    }
}
