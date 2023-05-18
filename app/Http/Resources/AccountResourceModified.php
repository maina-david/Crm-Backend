<?php

namespace App\Http\Resources;

use App\Helpers\AccessChecker;
use App\Models\AccountFormAttrOption;
use Auth;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResourceModified extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */

    public function toArray($request)
    {
        $account_details = $this->account_data;
        $account_detail_key_value = array();
        foreach ($account_details as $key => $account_detail) {
            $option = $account_detail->value;
            if ($account_detail->account_form_attr->data_type == "checkbox" || $account_detail->account_form_attr->data_type == "radio" || $account_detail->account_form_attr->data_type == "Radio" || $account_detail->account_form_attr->data_type == "select") {
                $option = AccountFormAttrOption::find($account_detail->value)->option_name;
            }

            $approve_access = AccessChecker::has_account_aprove_access(Auth::user()->id);
            if (!$approve_access) {
                if ($account_detail->account_form_attr->is_masked) {
                    $option = substr_replace($option, "*", 3);
                }
            }
            $account_detail_key_value[$key]["data_type"] = $account_detail->account_form_attr->data_type;
            $account_detail_key_value[$key]["form_id"] = $account_detail->account_form_attr->id;
            $account_detail_key_value[$key]["lable"] = $account_detail->account_form_attr->name;
            $account_detail_key_value[$key]["data_id"] = $account_detail->id;
            $account_detail_key_value[$key]["value"] = $option;
        }
        // $account_detail_key_value["account_id"] = $this->id;

        return $account_detail_key_value;
    }
}
