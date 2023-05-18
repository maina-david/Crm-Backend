<?php

namespace App\Helpers;

use App\Models\ActiveAgentQueue;
use App\Models\AgentStatus;
use App\Models\CallAtribute;
use App\Models\CallLog;
use App\Models\CallServer;
use App\Models\CallTransferLog;
use App\Models\CampaignContact;
use App\Models\CDRTable;
use App\Models\Interaction;
use App\Models\QueueLog;

class CallHungupHelper
{
    protected static $call_status = "";

    public static function Hungup($call_id, $agent_hangup = false)
    {
        $call_log = CallLog::where("call_id", $call_id)->first();
        if ($call_log) {
            // return $call_log;
            if ($call_log->call_status == "ONCALL_FORWARDED") {

            } else if ($call_log->call_status == "ONIVR") {
                $call_log->update(["call_status" => "ABANDONED"]);
            } else if ($call_log->call_status == "ONCALL" || $call_log->call_status == "CALL_FORWARDED") {
                $call_atributes = CallAtribute::where("call_id", $call_id)->get();
                $call_time = 0;
                $hold_time = 0;
                $mute_time = 0;
                $queue_time = 0;
                $agent_answer_at = 0;
                self::$call_status = "ANSWERED";
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
                    }
                }
                if ($call_log->call_status == "ONCALL") {
                    $url = 'http://46.101.74.223/fileuploader.php';
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
                }
                $cdr_data = CDRTable::where("call_id", $call_id)->orderByDesc("id")->first();
                $old_cdr_data = CDRTable::where("call_id", $call_id)->selectRaw('SUM(call_time) as sum_call_time, SUM(hold_time) as sum_hold_time, SUM(mute_time) as sum_mute_time,SUM(queue_time) as sum_queue_time ')->first();
                if ($cdr_data) {
                    $cdr_data_update = CDRTable::find($cdr_data->id);
                    $cdr_data_update->desposition = self::$call_status;
                    $cdr_data_update->call_time = ($old_cdr_data->sum_call_time == null) ? $call_time : $call_time - $old_cdr_data->sum_call_time;
                    $cdr_data_update->hold_time = ($old_cdr_data->sum_hold_time == null) ? $hold_time : $hold_time - $old_cdr_data->sum_hold_time;
                    $cdr_data_update->mute_time = ($old_cdr_data->sum_mute_time == null) ? $mute_time : $mute_time - $old_cdr_data->sum_mute_time;
                    $cdr_data_update->queue_time = ($old_cdr_data->sum_queue_time == null) ? $queue_time : $queue_time - $old_cdr_data->sum_queue_time;
                    $cdr_data_update->time_to_answer = $agent_answer_at;
                    $cdr_data_update->audio_url = "https://goipspace.fra1.cdn.digitaloceanspaces.com/recordings/" . $call_id . ".wav";
                    $url = "https://goipspace.fra1.cdn.digitaloceanspaces.com/recordings/" . $call_id . ".wav";
                    $headers = get_headers($url, true);
                    $filesize = $headers["Content-Length"];
                    $cdr_data_update->file_size = round($filesize / 1048576, 3);
                    $cdr_data_update->save();

                    $interactionCheck = Interaction::where([
                        'company_id' => $cdr_data_update->company_id,
                        'channel_id' => 7,
                        'interaction_reference' => $cdr_data_update->call_id,
                        'interaction_type' => 'voice'
                    ])->first();
                    if ($call_log->call_status == "ONCALL_FORWARDED") {
                        $transfer_log = CallTransferLog::where(["phone_channel" => $call_id])->latest()->first();
                        logger($transfer_log);
                        // CDRTable::create([
                        //     'call_id' => $call_id,
                        //     'phone_number' => $cdr_data_update->phone_number,
                        //     "bridge_id" => $transfer_log->transfer_bridge,
                        //     "group_id" => $cdr_data_update->group_id,
                        //     'call_date' => now(),
                        //     'call_type' => "FORWARDED_CALL",
                        //     'call_time' => 0,
                        //     "hold_time" => 0,
                        //     "mute_time" => 0,
                        //     'desposition' => "ONCALL",
                        //     'sip_id' => $transfer_log->user_transfered_to->sip->sip_id,
                        //     "user_id" => $transfer_log->transfered_to,
                        //     "queue_id" => $cdr_data_update->queue_id,
                        //     "company_id" => $cdr_data_update->company_id,
                        // ]);
                    } else if ($call_log->call_status == "ONCALL_FORWARDED") {

                    }
                    if (!$interactionCheck) {
                        $interaction = Interaction::create([
                            'company_id' => $cdr_data_update->company_id,
                            'channel_id' => 7,
                            'interaction_reference' => $cdr_data_update->call_id,
                            'interaction_type' => 'voice'
                        ]);
                    }

                    // return $call_atributes;
                }
                $queue_log_update = QueueLog::where("call_id", $call_id)->update([
                    "status" => ($call_log->call_status == "CALL_FORWARDED") ? "ONCALL" : "ANSWERED",
                    "call_time" => $call_time,
                    "hold_time" => $hold_time,
                    "mute_time" => $mute_time,
                    "queue_time" => $queue_time,
                ]);
                ActiveAgentQueue::where("sip_id", $cdr_data->sip_id)->update([
                    "status" => "ONLINE",
                    "last_call_hung_up_at" => now()
                ]);
                if($cdr_data->call_type=="SIPCALL"){
                    ActiveAgentQueue::where("sip_id", $cdr_data->phone_number)->update([
                        "status" => "ONLINE",
                        "last_call_hung_up_at" => now()
                    ]);
                }
                if ($call_log->call_type == "AGENT_CAMPAIGN") {
                    CampaignContact::where("id", $call_log->campaign_contact_id)->update(["desposition" => "CONTACTED"]);
                }
            } else if ($call_log->call_status == "RINGAGENT" || $call_log->call_status == "CALLING") {
                $call_atributes = CallAtribute::where("call_id", $call_id)->get();
                $call_time = 0;
                $hold_time = 0;
                $mute_time = 0;
                $queue_time = 0;
                foreach ($call_atributes as $key => $call_atribute) {
                    if ($call_atribute->attribute_name == "QUEUETIME") {
                        $call_start = $call_atribute->start_time;
                        $call_start = strtotime($call_atribute->start_time);
                        $call_end = ($call_atribute->end_time == null) ? strtotime(now()) : strtotime($call_atribute->end_time);
                        $queue_time += ($call_end - $call_start);
                    }
                }
                $cdr_data = CDRTable::where("call_id", $call_id)->orderByDesc("id")->first();
                if ($cdr_data) {
                    $cdr_data_update = CDRTable::find($cdr_data->id);
                    $cdr_data_update->desposition = "ABANDONED";
                    $cdr_data_update->queue_time = $queue_time;
                    $cdr_data_update->save();
                    logger("I am here changing the agent");
                }
                logger("DID I update that line I am here changing the agent");


                if ($agent_hangup) {
                    self::$call_status = "MOHPLAYING";
                } else {
                    self::$call_status = "ABANDONED";
                }

                $queue_log_update = QueueLog::where("call_id", $call_id)->update([
                    "status" => self::$call_status,
                    "call_time" => $call_time,
                    "hold_time" => $hold_time,
                    "mute_time" => $mute_time,
                    "queue_time" => $queue_time,
                    "user_id" => null,
                    "sip_id" => null
                ]);
                $user_session = AgentStatus::where(["user_id" => $cdr_data->user_id, "date" => date("Y-m-d")])->first();
                $current_penality = $user_session->current_penality;
                $penality = $user_session->penality;
                AgentStatus::where(["user_id" => $cdr_data->user_id, "date" => date("Y-m-d")])->update([
                    "current_penality" => $current_penality + 1,
                    "penality" => $penality + 1
                ]);
                ActiveAgentQueue::where("sip_id", $cdr_data->sip_id)->update([
                    "status" => "ONLINE",
                    "last_call_hung_up_at" => now(),
                    "penality" => $current_penality + 1
                ]);
                if ($call_log->call_type == "AGENT_CAMPAIGN") {
                    CampaignContact::where("id", $call_log->campaign_contact_id)->update(["desposition" => "ABANDONED"]);
                }
            } else if ($call_log->call_status == "MOHPLAYING") {
                $call_atributes = CallAtribute::where("call_id", $call_id)->get();
                self::$call_status = "ABANDONED";
                $call_atributes = CallAtribute::where("call_id", $call_id)->get();
                $call_time = 0;
                $hold_time = 0;
                $mute_time = 0;
                $queue_time = 0;
                foreach ($call_atributes as $key => $call_atribute) {
                    if ($call_atribute->attribute_name == "QUEUETIME") {
                        $call_start = $call_atribute->start_time;
                        $call_start = strtotime($call_atribute->start_time);
                        $call_end = ($call_atribute->end_time == null) ? strtotime(now()) : strtotime($call_atribute->end_time);
                        $queue_time += ($call_end - $call_start);
                    }
                }

                $queue_log_update = QueueLog::where("call_id", $call_id)->update([
                    "status" => self::$call_status,
                    "call_time" => $call_time,
                    "hold_time" => $hold_time,
                    "mute_time" => $mute_time,
                    "queue_time" => $queue_time,
                ]);
            } else if ($call_log->call_status == "RINGING") {
                $call_atributes = CallAtribute::where("call_id", $call_id)->get();
                $call_time = 0;
                $hold_time = 0;
                $mute_time = 0;
                $queue_time = 0;
                foreach ($call_atributes as $key => $call_atribute) {
                    if ($call_atribute->attribute_name == "QUEUETIME") {
                        $call_start = $call_atribute->start_time;
                        $call_start = strtotime($call_atribute->start_time);
                        $call_end = ($call_atribute->end_time == null) ? strtotime(now()) : strtotime($call_atribute->end_time);
                        $queue_time += ($call_end - $call_start);
                    }
                }
                $cdr_data = CDRTable::where("call_id", $call_id)->orderByDesc("id")->first();
                if ($cdr_data) {
                    $cdr_data_update = CDRTable::find($cdr_data->id);
                    $cdr_data_update->desposition = "NOANSWER";
                    $cdr_data_update->queue_time = $queue_time;
                    $cdr_data_update->save();
                }

                $call_log->update(["call_status" => "NOANSWER"]);
                if ($agent_hangup) {
                    self::$call_status = "MOHPLAYING";
                } else {
                    self::$call_status = "ABANDONED";
                }

                $queue_log_update = QueueLog::where("call_id", $call_id)->update([
                    "status" => "NOANSWER",
                    "call_time" => $call_time,
                    "hold_time" => $hold_time,
                    "mute_time" => $mute_time,
                    "queue_time" => $queue_time,
                    "user_id" => null,
                    "sip_id" => null
                ]);
                ActiveAgentQueue::where("sip_id", $cdr_data->sip_id)->update([
                    "status" => "ONLINE",
                    "last_call_hung_up_at" => now()
                ]);
                if ($call_log->call_type == "AGENT_CAMPAIGN") {
                    CampaignContact::where("id", $call_log->campaign_contact_id)->update(["desposition" => "NOANSWER"]);
                }
            }
            if (self::$call_status != "") {
                $call_log->call_status = self::$call_status;
                $call_log->save();
            }
        }
        return [$call_id, $call_log, self::$call_status];
    }
}