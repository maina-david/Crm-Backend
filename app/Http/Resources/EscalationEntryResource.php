<?php

namespace App\Http\Resources;

use App\Models\FormAttribute;
use App\Models\FormAttributeOption;
use Illuminate\Http\Resources\Json\JsonResource;

class EscalationEntryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $form_item = FormAttribute::find($this->escation_form_item_id);

        if ($form_item->data_type == "checkbox" || $form_item->data_type == "radio" || $form_item->data_type == "Radio" || $form_item->data_type == "select") {
            $option = FormAttributeOption::find($this->value);
            $value = $option->option_name;
        } else {
            $value = $this->value;
        }
        return [$form_item->name => $value];
    }
}