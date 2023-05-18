<?php

namespace App\Handler;

use App\Helpers\CallControlHelper;
use App\Helpers\QueueHelper;
use App\Models\CallIvrLog;
use App\Models\CallLog;
use App\Models\CallServer;
use App\Models\Campaign;
use App\Models\CampaignContact;
use App\Models\DidList;
use App\Models\IVRFlow;
use App\Models\IVRLink;
use App\Models\QueueLog;
use \Spatie\WebhookClient\ProcessWebhookJob;


class MusicStoppedWebhook extends ProcessWebhookJob
{
    var $company_id;
    public function handle()
    {
        $play_id = substr($this->webhookCall['payload']['eventid'], 17);
        $call_ivr_log = CallIvrLog::where("data", $play_id)->first();

        if ($call_ivr_log) {
            $previous_ivr = IVRFlow::find($call_ivr_log->currnt_ivr_flow);

            $call_log = CallLog::find($call_ivr_log->call_log_id);
            $call_server = CallServer::where("server_name", $call_log->source)->first();
            if ($previous_ivr->status == "DTMFINSERTED") {
                /////skip
            } else {
                $this->company_id = $call_ivr_log->company_id;
                $call_data["channel_id"] = $call_ivr_log->call_log_id;

                //////////////////////////
                $call_log = CallLog::find($call_ivr_log->call_log_id);
                $call_data["caller_id"] = $call_log->caller_id;
                if ($previous_ivr->application_type == "Background" && $previous_ivr->application_type != "Hungup") {
                    sleep(5);
                    if ($call_ivr_log->retry == 2) {
                        \App\Helpers\CallControlHelper::delete_channel($call_data["channel_id"], $call_server->ip_address . ":" . $call_server->port);
                        CallIvrLog::where("id", $call_ivr_log->id)->update([
                            'retry' => ($call_ivr_log->retry + 1),
                            'data' => "Hungup",
                            "next_ivr_flow" => "Hungup"
                        ]);
                    } else {
                        $check_current_ivr_log = CallIvrLog::find($call_ivr_log->id);
                        if ($check_current_ivr_log->next_ivr_flow == null) {
                            $error_audio = IVRLink::where(["selection" => "t", "ivr_flow_id" => $check_current_ivr_log->currnt_ivr_flow])->first();
                            if ($error_audio) {
                                $error_ivr = IVRFlow::where("id", $error_audio->next_flow_id);
                                $play_audio_data = \App\Helpers\CallControlHelper::playaudio($call_data["channel_id"], $error_ivr->application_data, $call_server->ip_address . ":" . $call_server->port);
                                $play_audio = json_decode($play_audio_data[1])->id;
                                CallIvrLog::where("id", $call_ivr_log->id)->update([
                                    'retry' => ($call_ivr_log->retry + 1),
                                    'data' => $play_audio,
                                ]);
                            } else {
                                // $error_ivr=IVRFlow::where("id",$error_audio->next_flow_id);
                                $play_audio_data = \App\Helpers\CallControlHelper::playaudio($call_data["channel_id"], $previous_ivr->application_data, $call_server->ip_address . ":" . $call_server->port);
                                $play_audio = json_decode($play_audio_data[1])->id;
                                CallIvrLog::where("id", $call_ivr_log->id)->update([
                                    'retry' => $call_ivr_log->retry + 1,
                                    'data' => $play_audio,
                                ]);
                            }
                        }
                    }
                } else if ($previous_ivr->application_type == "Hungup") {
                    ////////skip
                } else {
                    $this->run_ivr($previous_ivr, $call_data);
                }
            }
        } else {
            $queue_log = QueueLog::with("queue")->where("moh_play_id", $play_id)->first();
            if ($queue_log) {
                if ($queue_log->status == "MOHPLAYING" || $queue_log->status == "RINGAGENT") {
                    $queue_timeout = $queue_log->queue->time_out;
                    $timeFirst  = strtotime($queue_log->created_at);
                    $timeSecond = strtotime(now());
                    $differenceInSeconds = $timeSecond - $timeFirst;
                    $bridge_id = $queue_log->bridge_out_id;
                    $channel_id = $queue_log->call_id;
                    $call_log = CallLog::find($queue_log->call_id);
                    $call_server = CallServer::where("server_name", $call_log->source)->first();
                    $queue_log_update = QueueLog::find($queue_log->id);
                    if ($queue_timeout > $differenceInSeconds || $queue_log->queue->time_out == 0) {
                        $moh_url = $queue_log->moh_files;
                        $pay_audio = "";
                        $call_log = CallLog::find($queue_log->call_id);
                        $call_server = CallServer::where("server_name", $call_log->source)->first();
                        try {
                            $pay_audio = CallControlHelper::play_audio($bridge_id, $moh_url, $call_server->ip_address . ":" . $call_server->port);
                        } catch (\Exception $ex) {
                            //already hungup 

                        }
                        $play_id = json_decode($pay_audio[1])->id;
                        $queue_log_update->moh_play_id = $play_id;
                        $queue_log_update->save();
                    } else {
                        try {
                            $hangup_response = CallControlHelper::delete_channel($channel_id, $call_server->ip_address . ":" . $call_server->port);
                            CallControlHelper::delete_bridge($bridge_id, $call_server->ip_address . ":" . $call_server->port);
                            $queue_log_update->status = "TIMEOUT";
                            $queue_log_update->save();
                        } catch (\Exception $ex) {
                        }
                    }
                }
            }
        }
        $call_log_data = CallLog::where("play_id", $play_id)->first();
        if ($call_log_data) {
            if ($call_log_data->call_type == "VOICEBROADCAST") {
                $call_start = strtotime($call_log_data->updated_at);
                $call_end = strtotime(now());
                $call_time = ($call_end - $call_start);
                $campaign_contact = CampaignContact::find($call_log_data->campaign_contact_id);
                $campaign_contact->update(["call_time" => $call_time, "status" => "CONTACTED", "desposition" => "ANSWERED"]);
            }
            $channel_id = $call_log_data->call_id;
            $call_server = CallServer::where("server_name", $call_log_data->source)->first();
            \App\Helpers\CallControlHelper::delete_channel($channel_id, $call_server->ip_address . ":" . $call_server->port);
        }
    }


    public function run_ivr($starting_point, $call_data)
    {
        $first_node = IVRFlow::where("parent_id", $starting_point->id)->first();
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
            } else if ($first_node->application_type == "Wait") {
                sleep($first_node->application_data);
                $this->run_ivr($first_node,  $call_data["channel_id"], $call_server);
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

            $current_call->call_status = "ONIVR";
            $current_call->save();
            $starting_point->next_ivr_flow = $first_node->id;
        } else {
            \App\Helpers\CallControlHelper::delete_channel($call_data["channel_id"], $call_server->ip_address . ":" . $call_server->port);
        }
    }
}
