<?php

namespace App\Http\Resources;

use App\Models\OutboundSipDid;
use Illuminate\Http\Resources\Json\JsonResource;

class AgentOutboundDIDMapResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $agent["id"] = $this->user_id;
        $agent["name"] = $this->users->name;
        $agent["sip"] = $this->users->sip->sip_id;
        $agent["did"] = null;
        $agent["did_id"] = null;
        $outbound_did = OutboundSipDid::where("sip_id", $this->users->sip_id)->first();
        if ($outbound_did) {
            $agent["did"] = $outbound_did->did->did;
            $agent["did_id"] = $outbound_did->did_id;
        }
        return $agent;
    }
}