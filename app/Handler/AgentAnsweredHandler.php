<?php

namespace App\Handler;

use App\Helpers\AdminDashboardHelper;
use App\Helpers\CallControlHelper;
use App\Models\ActiveAgentQueue;
use App\Models\CallAtribute;
use App\Models\CallLog;
use App\Models\CallServer;
use App\Models\CallTransferLog;
use App\Models\CDRTable;
use App\Models\OutboundCallLogs;
use App\Models\QueueLog;
use App\Services\PhoneFormatterService;
use \Spatie\WebhookClient\ProcessWebhookJob;


class AgentAnsweredHandler extends ProcessWebhookJob
{
    public function handle()
    {
        logger("AGENT ANSWERED HANDLER");
        logger($this->webhookCall);
        logger("+++++++++++++++++++++++++++++++++");
        $call_id = $this->webhookCall['payload']['callid'];
        $sip_id = $this->webhookCall['payload']['callerid'];
        $queue_log = QueueLog::where(["sip_id" => $sip_id, "status" => "RINGAGENT"])->first();

        $transfer_log = CallTransferLog::where("forwarded_channel", $call_id)->first();
        if ($queue_log) {
            if ($queue_log->status = "RINGAGENT") {
                $bridge_id = $queue_log->bridge_out_id;
                $queue_log_update = QueueLog::find($queue_log->id);
                $queue_log_update->status = "ONCALL";
                $queue_log_update->save();

                // CDRTable::where([
                //     "call_id" => $queue_log->call_id,
                //     "sip_id" => $sip_id
                // ])->update([
                //         "desposition" => "ONCALL"
                //     ]);

                $cdr_data = CDRTable::where([
                    "call_id" => $queue_log->call_id,
                    "sip_id" => $sip_id
                ])->latest()->first();

                $cdr_data->update(["desposition"=>"ONCALL"]);

                $call_log = CallLog::find($queue_log->call_id);
                $call_log->call_status = "ONCALL";
                $call_log->save();

                $active_agent_queue = ActiveAgentQueue::where("sip_id", $sip_id)->update(["status" => "ONCALL"]);

                $call_atribute = CallAtribute::create([
                    "call_id" => $queue_log->call_id,
                    "attribute_name" => "CALLTIME",
                    "start_time" => now()
                ]);


                ///////////call event to CALL_IN_IVR
                $call_log = CallLog::find($queue_log->call_id);
                AdminDashboardHelper::call_in_ivr($call_log->company_id);
                AdminDashboardHelper::agent_dashboard($queue_log->user_id);

                CallAtribute::where(["call_id" => $queue_log->call_id, "attribute_name" => "QUEUETIME"])->update(["end_time" => now()]);
                CallAtribute::where(["call_id" => $queue_log->call_id, "sip_id" => $queue_log->sip_id, "attribute_name" => "AGENTRINGTIME"])->update(["end_time" => now()]);
                $call_server = CallServer::where("server_name", $call_log->source)->first();
                \App\Helpers\CallControlHelper::record_bridge($queue_log->call_id, $bridge_id, $call_server->ip_address . ":" . $call_server->port);
                \App\Helpers\CallControlHelper::delete_playback($queue_log->moh_play_id, $call_server->ip_address . ":" . $call_server->port);
                \App\Helpers\CallControlHelper::add_to_bridge($bridge_id, $call_id, $call_server->ip_address . ":" . $call_server->port);
            } else {

            }
        } else if ($transfer_log) {
            ////connecting transfer agent to the call handler agent
            $call_log = CallLog::find($transfer_log->agent_channel);
            if (!$call_log) {
                $call_log = CallLog::find($transfer_log->phone_channel);
            }
            $call_server_data = CallServer::where("server_name", $call_log->source)->first();
            $call_server = $call_server_data->ip_address . ":" . $call_server_data->port;
            CallControlHelper::add_to_bridge($transfer_log->transfer_bridge, $call_id, $call_server);
            CallControlHelper::delete_ring_channel($transfer_log->agent_channel, $call_server);
            CallControlHelper::add_to_bridge($transfer_log->transfer_bridge, $transfer_log->agent_channel, $call_server);

            ActiveAgentQueue::where("user_id", $transfer_log->transfered_to)->update(["status" => "ONCALL"]);
        } else {
            $call_log = CallLog::find($call_id);
            if ($call_log) {
                $phone_number = $call_log->caller_id;
                if (preg_match('/^[0-9]{12}+$/', $phone_number)) {
                    $phone = substr($phone_number, 3);
                    $phone_number = '0' . $phone;
                } else {
                    $phone = $phone_number;
                }
                $call_server = CallServer::where("type", "OUTBOUND")->first();
                // $call_log = CallLog::find($cdr_data);
                // return $cdr_data;
                if ($call_log->call_type == "SIPCALL") {
                    $call_sent_response = CallControlHelper::call_endpoint($phone_number, $call_log->did, $call_server->server_name, $call_server->ip_address . ":" . $call_server->port);
                } else {
                    $call_sent_response = CallControlHelper::call_phone_out($phone_number, $call_log->did, $call_server->server_name, $call_server->ip_address . ":" . $call_server->port);
                }
                CallControlHelper::ring_channel($call_log->call_id, $call_server->ip_address . ":" . $call_server->port);
                $call_log->update(["call_status" => "RINGING"]);
                QueueLog::where("call_id", $call_log->call_id)->update(["status" => "RINGING"]);
                CDRTable::where("call_id", $call_log->call_id)->update(["desposition" => "RINGING"]);
                $bridge_data = CallControlHelper::create_bridge($call_server->ip_address . ":" . $call_server->port);
                $bridge_id = json_decode($bridge_data[1])->id;

                \App\Helpers\CallControlHelper::add_to_bridge($bridge_id, $call_log->call_id, $call_server->ip_address . ":" . $call_server->port);
                // sleep(1);
                $outbound_log = OutboundCallLogs::where("sip_channel", $call_log->call_id)->first();
                ActiveAgentQueue::where("sip_id", $outbound_log->sip_id)->update(["status" => "ONCALL"]);
                AdminDashboardHelper::call_in_ivr($call_log->company_id);
                $outbound_log->update([
                    "phone_channel" => json_decode($call_sent_response[1])->id,
                    "sip_bridge" => $bridge_id
                ]);
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
                    ActiveAgentQueue::where("sip_id", $outbound_log->phone_number)->update(["status" => "ONCALL"]);
                    CDRTable::where("call_id", $call_log->call_id)->update(["desposition" => "ONCALL"]);
                    OutboundCallLogs::where("id", $outbound_log->id)->update(["status" => "ONCALL"]);

                    $call_atribute = CallAtribute::create([
                        "call_id" => $call_log->call_id,
                        "attribute_name" => "CALLTIME",
                        "start_time" => now()
                    ]);
                }
            }
        }
    }
}