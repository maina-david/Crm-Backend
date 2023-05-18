<?php

namespace App\Handler;

use App\Helpers\CallControlHelper;
use App\Models\CallLog;
use App\Models\CallServer;
use App\Models\Campaign;
use App\Models\CampaignContact;
use App\Models\CDRTable;
use App\Models\DidList;
use App\Models\OutboundSipDid;
use App\Models\SipList;
use App\Models\User;
use App\Models\VoiceBroadcastSetting;
use App\Services\PhoneFormatterService;
use \Spatie\WebhookClient\ProcessWebhookJob;


class ClickToCallHandler extends ProcessWebhookJob
{
    public function handle()
    {
        $call_data["channel_id"] = $this->webhookCall['payload']['callid'];
        $call_data["source"] = $this->webhookCall['payload']['source'];
        $call_data["sip"] = $this->webhookCall['payload']['sip'];
        $call_data["callerid"] = PhoneFormatterService::format_phone($this->webhookCall['payload']['phone_number']);


        ////////////look for DID
        $sip = SipList::where("sip_id", $call_data["sip"])->first();
        $did_to_call = OutboundSipDid::where("sip_id", $sip->id)->first();
        $did = DidList::find($did_to_call->did_id)->did;

        $check_did = $this->check_did($did);
        $call_log = CallLog::create([
            'call_id' => $call_data["channel_id"],
            'did' =>  $did,
            'call_status' => "CALLING",
            'call_type' => "CLICKTOCALL",
            'source' => $call_data["source"],
            'caller_id' => $call_data["callerid"],
            'company_id' => $check_did->company_id,
        ]);

        $call_server = CallServer::where("server_name", $call_data["source"])->first();
        $call_sent_response =  CallControlHelper::call_phone_out("0" . 
        substr($this->webhookCall['payload']['phone_number'], 3), $did, $call_server->server_name, $call_server->ip_address . ":" . $call_server->port);
        $bridge_data = CallControlHelper::create_bridge($call_server->ip_address . ":" . $call_server->port);
        $bridge_id = json_decode($bridge_data[1])->id;
        \App\Helpers\CallControlHelper::add_to_bridge($bridge_id, $call_data["channel_id"], $call_server->ip_address . ":" . $call_server->port);
        \App\Helpers\CallControlHelper::add_to_bridge($bridge_id, json_decode($call_sent_response[1])->id, $call_server->ip_address . ":" . $call_server->port);

        $user = User::where("sip_id", $sip->id)->first();
       CDRTable::create([
            'call_id' => $call_data["channel_id"],
            'phone_number' => $call_data["callerid"],
            "bridge_id" => $bridge_id,
            'call_date' => now(),
            'call_time' => 0,
            "hold_time" => 0,
            "mute_time" => 0,
            "audio_url"=>json_decode($call_sent_response[1])->id,
            'call_type' => "CLICKTOCALL",
            'desposition' => "RINGING",
            'sip_id' => $call_data["sip"],
            "user_id" => $user->id,
            "company_id" => $check_did->company_id,
        ]);
    }

    public function check_did($did)
    {
        $did_check = DidList::where(['did' => $did, 'allocation_status' => "ALLOCATED"])->first();
        if ($did_check) {
            if ($did_check->company_id == null || $did_check->ivr_id == null) {
                return false;
            } else {
                return $did_check;
            }
        }
        return false;
    }
}
