<?php

namespace App\Http\Resources;

use App\Models\TicketForm;
use App\Models\TicketFormItem;
use App\Models\TicketFormOption;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $form_item = $this->ticket_form;
        $value = $this->value;
        if ($form_item->data_type == "checkbox" || $form_item->data_type == "radio" || $form_item->data_type == "Radio" ||$form_item->data_type == "select") {
            $value = TicketFormOption::find($this->value)->option;
        }

        return [$this->ticket_form->lable => $value];
    }
}
