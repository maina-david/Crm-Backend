<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ContactLogResource extends JsonResource
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
            "log_id" => $this->id,
            "contact_id" => $this->contact_id,
            "action" => $this->action,            
            "creation_time" => $this->start_date,
            "approval_time" => $this->updated_at,
            "changed_by" => ($this->changedBy != null) ? $this->changedBy->name : "",
            "approvedBy" => ($this->approvedBy != null) ? $this->approvedBy->name : ""
        ];
    }
}
