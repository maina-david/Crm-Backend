<?php

namespace App\Http\Resources;

use App\Models\AccountFormAttrOption;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountFormAttrResource extends JsonResource
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
            'form_attribute_id' => $this->id,
            'name' => $this->name,
            'options' => AccountFormAttrOptionResource::collection($this->account_form_attr_options)
        ];
    }
}