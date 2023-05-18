<?php

namespace App\Handler;

use App\Events\TestEvent;
use App\Helpers\AdminDashboardHelper;
use App\Helpers\AgentStatusChangedEventHelper;
use App\Helpers\CallControlHelper;
use App\Helpers\CallHungupHelper;
use App\Models\ActiveAgentQueue;
use App\Models\CallAtribute;
use App\Models\CallLog;
use App\Models\CallServer;
use App\Models\CallTransferLog;
use App\Models\CDRTable;
use App\Models\OutboundCallLogs;
use App\Models\QueueLog;
use Spatie\WebhookClient\ProcessWebhookJob;

class BusyCallHandler extends ProcessWebhookJob
{
    public function handle()
    {
        logger("Busy Handler");
        logger($this->webhookCall['payload']);
        $outbound_log = OutboundCallLogs::where("phone_channel", $this->webhookCall['payload']['callid'])->first();
        if ($outbound_log) {
            $call_log = CallLog::find($outbound_log->sip_channel);
            if ($call_log) {
                if ($call_log->call_type == "SIPCALL") {
                    CDRTable::where("call_id", $call_log->call_id)->update(["desposition" => "NOANSWER"]);
                    ActiveAgentQueue::where("sip_id", $outbound_log->sip_id)->update(["status" => "ONLINE"]);
                    ActiveAgentQueue::where("sip_id", $outbound_log->phone_number)->update(["status" => "ONLINE"]);
                    OutboundCallLogs::where("id", $outbound_log->id)->update(["status" => "NOANSWER"]);


                    $cdr_for_hungup = CDRTable::where("call_id", $outbound_log->sip_id)->first();
                    $cdr_for_hungup2 = CDRTable::where("call_id", $outbound_log->phone_number)->first();
                    if ($cdr_for_hungup)
                        event(new TestEvent(strval($cdr_for_hungup->user_id), "sip_hungup", []));
                    if ($cdr_for_hungup2)
                        event(new TestEvent(strval($cdr_for_hungup2->user_id), "sip_hungup", []));
                } else if ($call_log->call_type == "CLICKTOCALL") {
                    if ($call_log->call_status == 'ONCALL') {
                        CallHungupHelper::Hungup($call_log->call_id);
                        CDRTable::where("call_id", $call_log->call_id)->update(["desposition" => "ANSWERED"]);
                        OutboundCallLogs::where("id", $outbound_log->id)->update(["status" => "ANSWERED"]);
                    } elseif ($call_log->call_status == 'RINGING') {
                        $call_log->call_status = 'NOANSWER';
                        $call_log->save();
                        CDRTable::where("call_id", $call_log->call_id)->update(["desposition" => "NOANSWER"]);
                        OutboundCallLogs::where("id", $outbound_log->id)->update(["status" => "NOANSWER"]);
                    }
                    ActiveAgentQueue::where("sip_id", $outbound_log->sip_id)->update(["status" => "ONLINE"]);
                    $cdr_for_hungup = CDRTable::where("call_id", $call_log->call_id)->first();
                    event(new TestEvent(strval($cdr_for_hungup->user_id), "sip_hungup", []));
                    AdminDashboardHelper::call_in_ivr($call_log->company_id);
                    AdminDashboardHelper::agent_dashboard($cdr_for_hungup->user_id);
                    AgentStatusChangedEventHelper::notify_agent_status($cdr_for_hungup->user_id);
                }
                $call_server = CallServer::where("type", "OUTBOUND")->first();
                $server = $call_server->ip_address . ":" . $call_server->port;
                try {
                    CallControlHelper::delete_channel($call_log->call_id, $server);
                } catch (\Exception $ex) {
                }
            }
        }
        $queue_log = QueueLog::where("channel_in_id", $this->webhookCall['payload']['callid'])->first();
        if ($queue_log) {
            if ($queue_log->status == "RINGAGENT") {
                $cdr_log = CDRTable::where(["call_id" => $queue_log->call_id, "desposition" => "RINGING"])->latest()->update(["desposition" => "ABANDONED"]);
                $queue_log->update(["status" => "MOHPLAYING"]);
                $active_agent = ActiveAgentQueue::where("sip_id", $queue_log->sip_id)->first();
                $penality = $active_agent->penality;
                ActiveAgentQueue::where("sip_id", $queue_log->sip_id)->update([
                    "status" => "ONLINE",
                    "last_call_hung_up_at" => now(),
                    "penality" => $penality + 1
                ]);
            }
        } else {
            $call_log = CallLog::find($this->webhookCall['payload']['callid']);
            $call_id = $this->webhookCall['payload']['callid'];
            if ($call_log) {
                if ($call_log->call_status == "ONCALL_FORWARDED") {
                    $time_calculated = $this->calcualte_call_time($call_id);
                    $record_call = $this->record_call($call_id);
                    $cdr_forwarded = CDRTable::where(["call_id" => $call_id, "call_type" => "FORWARDED_CALL", "desposition" => "ONCALL_FORWARDED"])->first();
                    $cdr_original = CDRTable::where(["call_id" => $call_id])->first();

                    $time_calculated["desposition"] = "ANSWERED";
                    $time_calculated["audio_url"] = "https://goipspace.fra1.cdn.digitaloceanspaces.com/recordings/" . $call_id . ".wav";
                    $time_calculated["file_size"] = $record_call;
                    $call_time_forward = $time_calculated["call_time_forward"];
                    unset($time_calculated["call_time_forward"]);
                    $cdr_forwarded->update($time_calculated);
                    $cdr_original->update($time_calculated);
                    $cdr_forwarded->update(["call_time" => $call_time_forward]);
                    ActiveAgentQueue::where("sip_id", $cdr_forwarded->sip_id)->update(["status" => "ONLINE"]);
                    $call_transfer = CallTransferLog::where("phone_channel", $call_id)->latest()->first();
                    $call_server = CallServer::where("type", "INBOUND")->first();
                    CallLog::where("call_id", $call_id)->update(["call_status" => "ANSWERED"]);
                    QueueLog::where("call_id", $call_id)->update([
                        "call_time" => $time_calculated["call_time"] + $call_time_forward,
                        "hold_time" => $time_calculated["hold_time"],
                        "mute_time" => $time_calculated["mute_time"],
                        "queue_time" => $time_calculated["queue_time"],
                        "status" => "ANSWERED"
                    ]);
                    try {
                        CallControlHelper::delete_channel($call_transfer->forwarded_channel, $call_server->ip_address . ":" . $call_server->port);
                        CallControlHelper::delete_bridge($call_transfer->transfer_bridge, $call_server->ip_address . ":" . $call_server->port);
                    } catch (\Exception $ex) {

                    }
                }
            }
        }
    }

    private function calcualte_call_time($call_id)
    {
        $call_atributes = CallAtribute::where("call_id", $call_id)->get();
        $call_time = 0;
        $hold_time = 0;
        $mute_time = 0;
        $queue_time = 0;
        $agent_answer_at = 0;
        $call_time_forward = 0;
        foreach ($call_atributes as $key => $call_atribute) {
            if ($call_atribute->attribute_name == "CALLTIME") {
                $call_start = $call_atribute->start_time;
                $call_start = strtotime($call_atribute->start_time);
                $call_end = strtotime(now());
                $call_time = ($call_end - $call_start);
                CallAtribute::where("id", $call_atribute->id)->update(["end_time" => now()]);
            } else if ($call_atribute->attribute_name == "CALLMUTE") {
                $call_start = $call_atribute->start_time;
                $call_start = strtotime($call_atribute->start_time);
                $call_end = ($call_atribute->end_time == null) ? strtotime(now()) : strtotime($call_atribute->end_time);
                $mute_time += ($call_end - $call_start);
            } else if ($call_atribute->attribute_name == "CALLHOLD") {
                $call_start = $call_atribute->start_time;
                $call_start = strtotime($call_atribute->start_time);
                $call_end = ($call_atribute->end_time == null) ? strtotime(now()) : strtotime($call_atribute->end_time);
                $hold_time += ($call_end - $call_start);
            } else if ($call_atribute->attribute_name == "QUEUETIME") {
                $call_start = $call_atribute->start_time;
                $call_start = strtotime($call_atribute->start_time);
                $call_end = ($call_atribute->end_time == null) ? strtotime(now()) : strtotime($call_atribute->end_time);
                $queue_time += ($call_end - $call_start);
            } else if ($call_atribute->attribute_name == "AGENTRINGTIME") {
                $call_start = $call_atribute->start_time;
                $call_start = strtotime($call_atribute->start_time);
                $call_end = ($call_atribute->end_time == null) ? strtotime(now()) : strtotime($call_atribute->end_time);
                $agent_answer_at += ($call_end - $call_start);
            } else if ($call_atribute->attribute_name == "CALLTIMEFORWARD") {
                $call_start = $call_atribute->start_time;
                $call_start = strtotime($call_atribute->start_time);
                $call_end = ($call_atribute->end_time == null) ? strtotime(now()) : strtotime($call_atribute->end_time);
                $call_time_forward += ($call_end - $call_start);
            }
        }
        return [
            "call_time" => $call_time,
            "hold_time" => $hold_time,
            "mute_time" => $mute_time,
            "queue_time" => $queue_time,
            "agent_answer_at" => $agent_answer_at,
            "call_time_forward" => $call_time_forward
        ];
    }

    private function record_call($call_id)
    {
        $url = 'http://46.101.74.223/fileuploader.php';
        $call_log = CallLog::find($call_id);
        if ($call_log->call_type != "INBOUND") {
            $call_server = CallServer::where("type", "OUTBOUND")->first();
            $url = 'http://' . $call_server->ip_address . '/fileuploader.php';
        }
        $curl = curl_init();
        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{"callid" : ' . $call_id . '}',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            )
        );
        $response = curl_exec($curl);
        curl_close($curl);

        $url = "https://goipspace.fra1.cdn.digitaloceanspaces.com/recordings/" . $call_id . ".wav";
        $headers = get_headers($url, true);
        $filesize = $headers["Content-Length"];
        $file_size = round($filesize / 1048576, 3);
        return $file_size;
    }
}