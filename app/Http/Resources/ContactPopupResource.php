<?php

namespace App\Http\Resources;

use App\Helpers\AccessChecker;
use App\Models\ContactFormAttrOpt;
use Auth;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactPopupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $contact_details = $this->contact_data;
        $contact_detail_key_value = array();
        foreach ($contact_details as $key => $contact_detail) {
            $option = $contact_detail->value;
            if ($contact_detail->contact_form_attr->data_type == "checkbox" || $contact_detail->contact_form_attr->data_type == "radio" || $contact_detail->contact_form_attr->data_type == "Radio" || $contact_detail->contact_form_attr->data_type == "select") {
                $option = ContactFormAttrOpt::find($contact_detail->value)->option_name;
            }
            $approve_access = AccessChecker::has_account_aprove_access(Auth::user()->id);
            if (!$approve_access) {
                if ($contact_detail->contact_form_attr->is_masked) {
                    $option = substr_replace($option, "*", 3);
                }
            }
            $contact_detail_key_value[$contact_detail->contact_form_attr->name] = $option;
        }
        $contact_detail_key_value["contact_id"] = $this->id;
        return $contact_detail_key_value;
    }
}
