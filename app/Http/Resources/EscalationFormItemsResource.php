<?php

namespace App\Http\Resources;

use App\Models\FormAttributeOption;
use Illuminate\Http\Resources\Json\JsonResource;

class EscalationFormItemsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $form_items = [
            'id' => $this->id,
            'name' => $this->name,
            'data_name' => $this->data_name,
            'is_required' => $this->is_required,
            'data_type' => $this->data_type,
            'is_masked' => $this->is_masked,
        ];

        if ($this->data_type == "checkbox" || $this->data_type == "radio" || $this->data_type == "Radio" || $this->data_type == "select") {
            $options = FormAttributeOption::where('form_attr_id', $this->id)->get();
            $form_items['options'] = $options;
        }
        return $form_items;
    }
}