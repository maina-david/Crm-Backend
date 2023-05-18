<?php

namespace App\Http\Resources;

use App\Models\Channel;
use App\Models\Conversation;
use Illuminate\Http\Resources\Json\JsonResource;

class SocialAccountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $social_account["contact_id"] = $this->contact_id;
        $social_account["account_type"] = Channel::find($this->channel_id)->name;
        $social_account["account_name"] = (Conversation::where("customer_id", $this->social_account)->orderBy("id", "DESC")->first())->customer_name;

        return $social_account;
    }
}
