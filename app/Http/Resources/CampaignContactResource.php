<?php

namespace App\Http\Resources;

use App\Models\Campaign;
use App\Models\SurveyResponse;
use App\Models\SurveyResponseData;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignContactResource extends JsonResource
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
        $camapign_contact = [
            "campaign_id" => $this->campaign_id,
            "campaign_contact_id" => $this->id,
            "campaign_name" => $this->campaign->name,
            "campaign_description" => $this->campaign->description,
            "contacted" => $this->status,
            "status" => $this->desposition
        ];
        $survey_response = SurveyResponse::where("campaign_contact_id", $this->id)->first();
        if ($survey_response) {
            $response_datas = SurveyResponseData::where("survey_response_id", $this->campaign_id);
            foreach ($response_datas as $key => $response_data) {
                
            }
        }

        return $camapign_contact;
    }
}
