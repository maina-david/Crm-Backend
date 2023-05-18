<?php

namespace App\Handler;

use App\Helpers\QueueHelper;
use App\Models\CallIvrLog;
use App\Models\CallLog;
use App\Models\CallServer;
use App\Models\DidList;
use App\Models\IVRFlow;
use App\Models\IVRLink;
use \Spatie\WebhookClient\ProcessWebhookJob;


class DTMFWebhook extends ProcessWebhookJob
{
    var $company_id;
    public function handle()
    {
        $input = $this->webhookCall['payload']['digit'];
        $callid = $this->webhookCall['payload']['callid'];

        $call_ivr_log = CallIvrLog::where("call_log_id", $callid)->orderByDesc("updated_at")->first();
        $this->company_id = $call_ivr_log->company_id;
        //////////////////////////
        $call_log = CallLog::find($call_ivr_log->call_log_id);
        // $call_server = CallServer::where("server_name", $call_log->source)->first();
        $call_server = CallServer::where("server_name", $call_log->source)->first();
        $call_data["caller_id"] = $call_log->caller_id;
        $ivr_flow = IVRFlow::where("id", $call_ivr_log->currnt_ivr_flow)->first();
        CallIvrLog::where("id", $call_ivr_log->id)->update([
            'status' => "DTMFINSERTED"
        ]);
        try {
            \App\Helpers\CallControlHelper::delete_playback($call_ivr_log->data, $call_server->ip_address . ":" . $call_server->port);
        } catch (\Exception $ex) {
            ////audio already stopped
        }
        if ($ivr_flow->application_type == "Background") {
            $selected_flow = IVRLink::where(["selection" => $input, "ivr_flow_id" => $ivr_flow->id])->first();
            if ($selected_flow) {
                $call_data["channel_id"] = $callid;
                $next_flow = IVRFlow::find($selected_flow->next_flow_id);
                CallIvrLog::where("id", $call_ivr_log->id)->update([
                    'next_ivr_flow' =>  $next_flow->id
                ]);
                $this->run_ivr($ivr_flow, $call_data, $next_flow);
            } else {
                if ($call_ivr_log->retry == 2) {
                    \App\Helpers\CallControlHelper::delete_channel($callid, $call_server->ip_address . ":" . $call_server->port);
                    CallIvrLog::where("id", $call_ivr_log->id)->update([
                        'retry' => ($call_ivr_log->retry + 1),
                        'data' => "Hungup",
                        "next_ivr_flow" => "Hungup"
                    ]);
                } else {
                    $error_audio = IVRLink::where(["selection" => "i", "ivr_flow_id" => $call_ivr_log->currnt_ivr_flow])->first();
                    if ($error_audio) {
                        $error_ivr = IVRFlow::where("id", $error_audio->next_flow_id);
                        $play_audio_data = \App\Helpers\CallControlHelper::playaudio($callid, $error_ivr->application_data, $call_server->ip_address . ":" . $call_server->port);
                        $play_audio = json_decode($play_audio_data[1])->id;
                        CallIvrLog::where("id", $call_ivr_log->id)->update([
                            'retry' => ($call_ivr_log->retry + 1),
                            'data' => $play_audio,
                        ]);
                    } else {
                        // $error_ivr=IVRFlow::where("id",$error_audio->next_flow_id);
                        $play_audio_data = \App\Helpers\CallControlHelper::playaudio($callid, $ivr_flow->application_data, $call_server->ip_address . ":" . $call_server->port);
                        $play_audio = json_decode($play_audio_data[1])->id;
                        CallIvrLog::where("id", $call_ivr_log->id)->update([
                            'retry' => $call_ivr_log->retry + 1,
                            'data' => $play_audio,
                        ]);
                    }
                }
            }
        }
    }

    public function run_ivr($starting_point, $call_data, $first_node = null)
    {
        if ($first_node == null) {
            $first_node = IVRFlow::where("parent_id", $starting_point->id)->first();
        }
        $current_call = CallLog::find($call_data["channel_id"]);
        $call_server = CallServer::where("server_name", $current_call->source)->first();
        if ($first_node) {

            $play_audio = null;
            if ($first_node->application_type == "PlayBack") {
                $play_audio_data = \App\Helpers\CallControlHelper::playaudio($call_data["channel_id"], $first_node->application_data, $call_server->ip_address . ":" . $call_server->port);
                $play_audio = json_decode($play_audio_data[1])->id;
            } else if ($first_node->application_type == "Background") {
                $play_audio_data = \App\Helpers\CallControlHelper::playaudio($call_data["channel_id"], $first_node->application_data, $call_server->ip_address . ":" . $call_server->port);
                $play_audio = json_decode($play_audio_data[1])->id;
            } else if ($first_node->application_type == "Queue") {
                ////queue_logic
                QueueHelper::send_to_queue($call_data["channel_id"], $first_node->application_data, $call_data["caller_id"]);
                return 0;
            } else if ($first_node->application_type == "Wait") {
                sleep($first_node->application_data);
                $this->run_ivr($first_node,  $call_data["channel_id"], null);
            } else if ($first_node->application_type == "Stop") {
                \App\Helpers\CallControlHelper::delete_channel($call_data["channel_id"], $call_server->ip_address . ":" . $call_server->port);
            }
            CallIvrLog::create([
                'call_log_id' => $call_data["channel_id"],
                'call_id' => $call_data["channel_id"],
                'currnt_ivr_flow' => $first_node->id,
                'data' => $play_audio,
                'company_id' => $this->company_id
            ]);
            $current_call = CallLog::find($call_data["channel_id"]);
            $current_call->call_status = "ONIVR";
            $current_call->save();
            $starting_point->next_ivr_flow = $first_node->id;
        } else {
            \App\Helpers\CallControlHelper::delete_channel($call_data["channel_id"], $call_server->ip_address . ":" . $call_server->port);
        }
    }
}
