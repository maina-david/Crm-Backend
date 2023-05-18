<?php

namespace App\Handler;

use App\Helpers\AdminDashboardHelper;
use App\Helpers\CallControlHelper;
use App\Helpers\CallHungupHelper;
use App\Models\CallAtribute;
use App\Models\CallLog;
use App\Models\CallServer;
use App\Models\CampaignContact;
use App\Models\CDRTable;
use App\Models\DidList;
use App\Models\OutboundCallLogs;
use App\Models\QueueLog;
use \Spatie\WebhookClient\ProcessWebhookJob;


class HangupWebhook extends ProcessWebhookJob
{
    public function handle()
    {
        logger("hanguphandler");
        logger($this->webhookCall['payload']);
        logger("++++++++++++++++++");
        $call_id = $this->webhookCall['payload']['callid'];
        $queue_log = QueueLog::where("call_id", $call_id)->first();
        try {
            $call_log = CallLog::find($call_id);
            if ($call_log) {
                CallHungupHelper::Hungup($call_id);
                ///////////voice broadcast
                if ($call_log->call_type == "VOICEBROADCAST") {
                    $campaign_contact = CampaignContact::find($call_log->campaign_contact_id);
                    if ($campaign_contact) {
                        $campaign_contact->update(["status" => "CONTACTED", "desposition" => "NOTANSWERED"]);
                        $call_log->updated(["call_status" => "ABANDONED"]);
                    }
                }
                ///////////call event to CALL_IN_IVR
                AdminDashboardHelper::call_in_ivr($call_log->company_id);
                if ($queue_log)
                    AdminDashboardHelper::agent_dashboard($queue_log->user_id);
                $call_server = CallServer::where("type", "INBOUND")->first();
                CallControlHelper::delete_channel($queue_log->channel_in_id, $call_server->ip_address . ":" . $call_server->port);
                CallControlHelper::delete_channel($call_id, $call_server->ip_address . ":" . $call_server->port);
            }

            $outbound_log = OutboundCallLogs::where("phone_channel", $call_id)->first();
            if ($outbound_log) {
                $call_server = CallServer::where("type", "OUTBOUND")->first();
                $server = $call_server->ip_address . ":" . $call_server->port;
                $outbound_log = OutboundCallLogs::where("phone_channel", $call_id)->first();
                CallHungupHelper::Hungup($outbound_log->sip_channel);
                CallControlHelper::delete_channel($outbound_log->sip_channel, $call_server->ip_address . ":" . $call_server->port);
            }
        } catch (\Exception $ex) {
        }
        // }
    }
}
