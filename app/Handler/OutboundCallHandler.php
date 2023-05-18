<?php

namespace App\Handler;

use App\Helpers\AdminDashboardHelper;
use App\Helpers\AgentStatusChangedEventHelper;
use App\Helpers\CallControlHelper;
use App\Models\ActiveAgentQueue;
use App\Models\CallAtribute;
use App\Models\CallLog;
use App\Models\CallServer;
use App\Models\Campaign;
use App\Models\CampaignContact;
use App\Models\CDRTable;
use App\Models\OutboundCallLogs;
use App\Models\QueueLog;
use App\Models\VoiceBroadcastSetting;
use App\Services\PhoneFormatterService;
use \Spatie\WebhookClient\ProcessWebhookJob;


class OutboundCallHandler extends ProcessWebhookJob
{
    public function handle()
    {
        $call_id = $this->webhookCall['payload']['callid'];
        $call_log = CallLog::find($call_id);
        logger("OUT BOUND HANDLER");
        logger($call_log);
        logger("++++++++++++++++++++");
        if ($call_log) {
            if ($call_log->call_type == "VOICEBROADCAST") {
                $campaign_contact = CampaignContact::find($call_log->campaign_contact_id);
                $voice_broadcast_setting = VoiceBroadcastSetting::where("campaign_id", $campaign_contact->campaign_id)->first();

                $call_server = CallServer::where("type", "OUTBOUND")->first();
                $server = $call_server->ip_address . ":" . $call_server->port;

                $play_id = CallControlHelper::playaudio($call_id, $voice_broadcast_setting->audio_url, $server);

                $call_log->update(['play_id' => json_decode($play_id[1])->id, "call_status" => "ANSWERED"]);
            }
            if ($call_log->call_type == "AGENT_CAMPAIGN") {
            }
        } else {
            $outbound_log = OutboundCallLogs::where("phone_channel", $call_id)->first();
            if ($outbound_log) {
                $call_server = CallServer::where("type", "OUTBOUND")->first();
                $server = $call_server->ip_address . ":" . $call_server->port;
                CallControlHelper::delete_ring_channel($outbound_log->sip_channel, $server);
                $bridge_id = $outbound_log->sip_bridge;
                CallControlHelper::add_to_bridge($bridge_id, $call_id, $server);

                $call_log = CallLog::find($outbound_log->sip_channel);
                CallControlHelper::record_bridge($call_log->call_id, $bridge_id, $call_server->ip_address . ":" . $call_server->port);

                QueueLog::where("call_id", $call_log->call_id)->update(["status" => "ONCALL"]);
                $call_log->update(["call_status" => "ONCALL"]);

                ActiveAgentQueue::where("sip_id", $outbound_log->sip_id)->update(["status" => "ONCALL"]);
                CDRTable::where("call_id", $call_log->call_id)->update(["desposition" => "ONCALL"]);
                $outbound_log->updated(["status" => "ONCALL"]);

                $active_agent = ActiveAgentQueue::where("sip_id", $outbound_log->sip_id)->first();
                AgentStatusChangedEventHelper::notify_agent_status($active_agent->user_id);
                $call_atribute = CallAtribute::create([
                    "call_id" => $call_log->call_id,
                    "attribute_name" => "CALLTIME",
                    "start_time" => now()
                ]);
                AdminDashboardHelper::call_in_ivr($call_log->company_id);
            }
        }
    }
}