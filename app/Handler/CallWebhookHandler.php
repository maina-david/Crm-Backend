<?php

namespace App\Handler;

use App\Helpers\AdminDashboardHelper;
use App\Helpers\CallControlHelper;
use App\Helpers\QueueHelper;
use App\Models\ActiveAgentQueue;
use App\Models\CallAtribute;
use App\Models\CallcenterHoliday;
use App\Models\CallcenterOffMusic;
use App\Models\CallcenterSetting;
use App\Models\CallIvrLog;
use App\Models\CallLog;
use App\Models\CallServer;
use App\Models\CDRTable;
use App\Models\DidList;
use App\Models\IVR;
use App\Models\IVRFlow;
use App\Models\MohFile;
use App\Models\Queue;
use App\Models\QueueLog;
use App\Models\WorkingHours;
use App\Services\PhoneFormatterService;
use Carbon\Carbon;
use \Spatie\WebhookClient\ProcessWebhookJob;


class CallWebhookHandler extends ProcessWebhookJob
{
    var $call_log_id;
    var $company_id;
    public function handle()
    {
        $call_data["channel_id"] = $this->webhookCall['payload']['callid'];
        $call_data["did"] = $this->webhookCall['payload']['did'];
        $call_data["source"] = $this->webhookCall['payload']['source'];
        $call_data["callerid"] = PhoneFormatterService::format_phone($this->webhookCall['payload']['callerid']);
        $check_did = $this->check_did($call_data["did"]);
        $call_server = CallServer::where("server_name", $call_data["source"])->first();
        if ($check_did) {
            $this->company_id = $check_did->company_id;
            $starting_point = IVRFlow::where(['ivr_id' => $check_did->ivr_id, "parent_id" => null])->first();
            $this->call_log_id = CallLog::create([
                'call_id' => $call_data["channel_id"],
                'did' => $call_data["did"],
                'source' => $call_data["source"],
                'caller_id' => $call_data["callerid"],
                'call_status' => "INCOMIMG",
                'company_id' => $this->company_id
            ]);
            ///check callcenter on /off
            $callcenter_status = true;
            $callcenter_status_check = CallcenterSetting::where("company_id", $this->company_id)->first();
            if ($callcenter_status_check) {
                if ($callcenter_status_check->status == "DEACTIVATED") {
                    $callcenter_status = false;
                    $off_audio = CallcenterOffMusic::with("file")->where("company_id", $this->company_id)->first();
                    if ($off_audio) {
                        $play_audio_data = \App\Helpers\CallControlHelper::playaudio($call_data["channel_id"], $off_audio->file->url, $call_server->ip_address . ":" . $call_server->port);
                        $this->call_log_id->play_id = json_decode($play_audio_data[1])->id;
                    } else {
                        \App\Helpers\CallControlHelper::delete_channel($call_data["channel_id"], $call_server->ip_address . ":" . $call_server->port);
                    }
                    $this->call_log_id->call_status = "OFFLINE";
                    CallLog::where("call_id", $this->call_log_id->call_id)->update($this->call_log_id);
                }
            }

            //////working hour checking
            $working_hour_check = $this->working_hour($this->company_id);
            if (!$working_hour_check["should_ring"]) {
                $play_audio_data = \App\Helpers\CallControlHelper::playaudio($call_data["channel_id"], $working_hour_check["audio_url"], $call_server->ip_address . ":" . $call_server->port);
                $this->call_log_id->call_status = "WORKINGHOUR";
                $this->call_log_id->play_id = json_decode($play_audio_data[1])->id;
                $this->call_log_id->save();
            }
            //////checking holiday
            $check_holiday = $this->check_holiday($this->company_id);
            if ($check_holiday["is_holiday"]) {
                $play_audio_data = \App\Helpers\CallControlHelper::playaudio($call_data["channel_id"], $working_hour_check["audio_url"], $call_server->ip_address . ":" . $call_server->port);
                $this->call_log_id->call_status = "HOLIDAY";
                $this->call_log_id->play_id = json_decode($play_audio_data[1])->id;
                $this->call_log_id->save();
            }
            if ($working_hour_check["should_ring"] && !$check_holiday["is_holiday"] && $callcenter_status) {
                $current_time = Carbon::now();
                $calls_before = $current_time->addMinutes(-(30));

                $previous_queue = QueueLog::where(["caller_id" => $call_data["callerid"]])
                    ->whereIn("call_type", ["AGENT_CAMPAIGN"])
                    ->where("updated_at", ">", $calls_before)->first();
                $previous_cdr = CDRTable::where(["phone_number" => $call_data["callerid"]])
                    ->whereIn("call_type", ["CLICKTOCALL"])
                    ->where("updated_at", ">", $calls_before)->first();
                if ($previous_queue) {
                    $last_call = QueueLog::where("call_id", $previous_queue->call_id)->first();
                    if ($last_call) {
                        $agent_status = ActiveAgentQueue::where(["user_id" => $last_call->user_id])->first();
                        if ($agent_status->status == "ONLINE" && $agent_status->sip_status == "ONLINE") {
                            $queue_log = QueueLog::create([
                                "call_id" => $call_data["channel_id"],
                                "queue_id" => $previous_queue->queue_id,
                                "caller_id" => $call_data["callerid"],
                                "company_id" => $this->company_id
                            ]);

                            $queue = Queue::with('moh')->find($previous_queue->queue_id);
                            $url_list = "";
                            if ($queue->moh_id != null) {
                                $moh_files = MohFile::where('moh_id', $queue->moh_id)->orderBy('sequence')->get();
                                $url_list = QueueHelper::change_to_list($moh_files);
                            }
                            if ($url_list == "") {
                                $url_list = "https://goipspace.fra1.cdn.digitaloceanspaces.com/call_center/default_moh/monolomoh.wav";
                            }

                            $bridge_data = CallControlHelper::create_bridge($call_server->ip_address . ":" . $call_server->port);
                            $bridge_id = json_decode($bridge_data[1])->id;
                            CallControlHelper::add_to_bridge($bridge_id, $call_data["channel_id"], $call_server->ip_address . ":" . $call_server->port);
                            $pay_audio = CallControlHelper::play_audio($bridge_id, $url_list, $call_server->ip_address . ":" . $call_server->port);
                            $play_id = json_decode($pay_audio[1])->id;
                            $queue_log->moh_play_id = $play_id;
                            $queue_log->bridge_out_id = $bridge_id;
                            $queue_log->moh_files = $url_list;
                            $queue_log->status = "RINGAGENT";
                            $queue_log->group_id = $queue->group_id;
                            $queue_log->save();

                            $call_log = CallLog::find($call_data["channel_id"]);
                            $call_log->call_status = "RINGAGENT";
                            $call_log->save();


                            CallAtribute::create([
                                'call_id' => $call_data["channel_id"],
                                "attribute_name" => "QUEUETIME",
                                "start_time" => now()
                            ]);


                            $channel_out_id = \App\Helpers\CallControlHelper::call_endpoint($last_call->sip_id, $call_data["callerid"], $call_server->server_name, $call_server->ip_address . ":" . $call_server->port);
                            CDRTable::create([
                                'call_id' => $call_data["channel_id"],
                                'phone_number' => $call_data["callerid"],
                                "bridge_id" => $bridge_id,
                                "group_id" => $queue->group_id,
                                'call_date' => now(),
                                'call_time' => 0,
                                "hold_time" => 0,
                                "mute_time" => 0,
                                'desposition' => "RINGING",
                                'sip_id' => $last_call->sip_id,
                                "user_id" => $last_call->user_id,
                                "queue_id" => $last_call->queue_id,
                                "company_id" => $queue->company_id,
                            ]);
                            $queue_log->status = "RINGAGENT";
                            $queue_log->sip_id = $previous_queue->sip_id;
                            $queue_log->user_id = $previous_queue->user_id;
                            $queue_log->channel_in_id = json_decode($channel_out_id[1])->id;
                            $queue_log->save();
                            $call_log_update = CallLog::find($call_data["channel_id"]);
                            $call_log_update->call_status = "RINGAGENT";
                            $call_log_update->save();
                            CallAtribute::create([
                                'call_id' => $call_data["channel_id"],
                                'sip_id' => $last_call->sip_id,
                                "attribute_name" => "AGENTRINGTIME",
                                "start_time" => now()
                            ]);
                            ActiveAgentQueue::where("user_id", $last_call->user_id)->update(["status" => "RINGAGENT"]);
                        } else {
                            $this->run_ivr($starting_point, $call_data, $call_server);
                        }
                    } else {
                        $this->run_ivr($starting_point, $call_data, $call_server);
                    }
                } else if ($previous_cdr) {
                    $agent_status = ActiveAgentQueue::where(["user_id" => $previous_cdr->user_id])->first();
                    if ($agent_status->status == "ONLINE" && $agent_status->sip_status == "ONLINE") {
                        CallControlHelper::ring_channel($previous_cdr->call_id, $call_server->ip_address . ":" . $call_server->port);
                        $bridge_data = CallControlHelper::create_bridge($call_server->ip_address . ":" . $call_server->port);
                        $bridge_id = json_decode($bridge_data[1])->id;
                        CallControlHelper::add_to_bridge($bridge_id, $call_data["channel_id"], $call_server->ip_address . ":" . $call_server->port);

                        $call_log = CallLog::find($call_data["channel_id"]);
                        $call_log->call_status = "RINGAGENT";
                        $call_log->save();
                        CallAtribute::create([
                            'call_id' => $call_data["channel_id"],
                            "attribute_name" => "QUEUETIME",
                            "start_time" => now()
                        ]);

                        $channel_out_id = \App\Helpers\CallControlHelper::call_endpoint($previous_cdr->sip_id, $call_data["callerid"], $call_server->server_name, $call_server->ip_address . ":" . $call_server->port);
                        CDRTable::create([
                            'call_id' => $call_data["channel_id"],
                            'phone_number' => $call_data["callerid"],
                            "bridge_id" => $bridge_id,
                            'call_date' => now(),
                            'call_time' => 0,
                            "hold_time" => 0,
                            "mute_time" => 0,
                            'desposition' => "RINGING",
                            'sip_id' => $previous_cdr->sip_id,
                            "user_id" => $previous_cdr->user_id,
                            "company_id" => $previous_cdr->company_id,
                        ]);
                        $call_log_update = CallLog::find($call_data["channel_id"]);
                        $call_log_update->call_status = "RINGAGENT";
                        $call_log_update->save();
                        CallAtribute::create([
                            'call_id' => $call_data["channel_id"],
                            'sip_id' => $previous_cdr->sip_id,
                            "attribute_name" => "AGENTRINGTIME",
                            "start_time" => now()
                        ]);
                        ActiveAgentQueue::where("user_id", $previous_cdr->user_id)->update(["status" => "RINGAGENT"]);
                    } else {
                        $this->run_ivr($starting_point, $call_data, $call_server);
                    }
                } else {
                    $this->run_ivr($starting_point, $call_data, $call_server);
                }
            }
        } else {
            //hungup 
            \App\Helpers\CallControlHelper::delete_channel($call_data["channel_id"], $call_server->ip_address . ":" . $call_server->port);
        }
    }

    public function run_ivr($starting_point, $call_data, $call_server)
    {
        $first_node = IVRFlow::where("parent_id", $starting_point->id)->first();
        if ($first_node) {
            $play_audio = null;
            if ($first_node->application_type == "PlayBack") {
                $play_audio_data = \App\Helpers\CallControlHelper::playaudio($call_data["channel_id"], $first_node->application_data, $call_server->ip_address . ":" . $call_server->port);
                $play_audio = json_decode($play_audio_data[1])->id;

                $current_call = CallLog::find($call_data["channel_id"]);
                $current_call->call_status = "ONIVR";
                $current_call->save();
            } else if ($first_node->application_type == "Background") {
                $play_audio_data = \App\Helpers\CallControlHelper::playaudio($call_data["channel_id"], $first_node->application_data, $call_server->ip_address . ":" . $call_server->port);
                $play_audio = json_decode($play_audio_data[1])->id;

                $current_call = CallLog::find($call_data["channel_id"]);
                $current_call->call_status = "ONIVR";
                $current_call->save();
            } else if ($first_node->application_type == "Queue") {
                ////queue_logic
                QueueHelper::send_to_queue($call_data["channel_id"], $first_node->application_data, $call_data["callerid"]);
            } else if ($first_node->application_type == "Wait") {
                sleep($first_node->application_data);
                $this->run_ivr($first_node, $call_data["channel_id"], $call_server);
                $current_call = CallLog::find($call_data["channel_id"]);
                $current_call->call_status = "ONIVR";
                $current_call->save();
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

            ///////////call event to CALL_IN_IVR
            AdminDashboardHelper::call_in_ivr($this->company_id);
        } else {
            \App\Helpers\CallControlHelper::delete_channel($call_data["channel_id"], $call_server->ip_address . ":" . $call_server->port);
        }
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
        } else {
            $did_check = DidList::where(['did' => "+" . $did, 'allocation_status' => "ALLOCATED"])->first();
            if ($did_check) {
                if ($did_check->company_id == null || $did_check->ivr_id == null) {
                    return false;
                } else {
                    return $did_check;
                }
            }
        }
        return false;
    }

    public function working_hour($company_id)
    {
        $should_ring = true;
        $url = "";
        $today = Carbon::now()->isoFormat('dddd');

        $working_hour = WorkingHours::with("file")->where(["date" => $today, "company_id" => $company_id])->first();
        if ($working_hour) {
            $should_ring = $this->check_time($working_hour->start_time, $working_hour->end_time);
            $url = $working_hour->file->url;
        }
        return ["should_ring" => $should_ring, "audio_url" => $url];
    }

    public function check_time($from, $to)
    {
        $current_hour = Carbon::now()->isoFormat('HH:mm');

        Carbon::macro('isTimeBefore', static function ($other) {
            return self::this()->format('Gis.u') < $other->format('Gis.u');
        });
        Carbon::macro('isTimeAfter', static function ($other) {
            return self::this()->format('Gis.u') > $other->format('Gis.u');
        });

        $is_before = Carbon::parse($current_hour)->isTimeBefore(Carbon::parse($to));
        $is_after = Carbon::parse($current_hour)->isTimeAfter(Carbon::parse($from));

        if ($is_after && $is_before) {
            return true;
        } else {
            return false;
        }
    }

    public function check_holiday($company_id)
    {
        $today = date('Y-m-d');
        $is_holiday = false;
        $url = "";
        $holiday = CallcenterHoliday::with("file")->where(["date" => $today, "company_id" => $company_id])->first();
        if ($holiday) {
            $is_holiday = true;
            $url = $holiday->file->url;
        }
        return ["is_holiday" => $is_holiday, "url" => $url];
    }
}