<?php

namespace App\Helpers;

use App\Models\CallAtribute;
use App\Models\CallLog;
use App\Models\CallServer;
use App\Models\MohFile;
use App\Models\Queue;
use App\Models\QueueLog;
use PhpParser\JsonDecoder;

class QueueHelper
{
    public function join_empty($queue_id)
    {
        $queue_to_check = Queue::find($queue_id);
        return ($queue_to_check->join_empty == "Yes") ? true : false;
    }

    public function check_agent_in_queue($queue_id)
    {
    }

    public static function send_to_queue($call_id, $queue_id, $caller_id)
    {
        /******Entry requirment */
        $call_log_check = CallLog::find($call_id);
        $call_server = CallServer::where("server_name", $call_log_check->source)->first();
        $queue_log = QueueLog::create([
            "call_id" => $call_id,
            "queue_id" => $queue_id,
            "caller_id" => $caller_id,
            "company_id" => $call_log_check->company_id
        ]);
        $queue = Queue::with('moh')->find($queue_id);
        $url_list = "";
        if ($queue->moh_id != null) {
            $moh_files = MohFile::where('moh_id', $queue->moh_id)->orderBy('sequence')->get();
            $url_list = self::change_to_list($moh_files);
        }
        if ($url_list == "") {
            $url_list = "sound:https://goipspace.fra1.cdn.digitaloceanspaces.com/call_center/default_moh/monolomoh.wav";
        }
        $bridge_data = CallControlHelper::create_bridge($call_server->ip_address . ":" . $call_server->port);
        $bridge_id = json_decode($bridge_data[1])->id;
        CallControlHelper::add_to_bridge($bridge_id, $call_id, $call_server->ip_address . ":" . $call_server->port);
        $pay_audio = CallControlHelper::play_audio($bridge_id, $url_list, $call_server->ip_address . ":" . $call_server->port);
        $play_id = json_decode($pay_audio[1])->id;
        $queue_log->moh_play_id = $play_id;
        $queue_log->bridge_out_id = $bridge_id;
        $queue_log->moh_files = $url_list;
        $queue_log->status = "MOHPLAYING";
        $queue_log->group_id = $queue->group_id;
        $queue_log->save();

        $call_log = CallLog::find($call_id);
        $call_log->call_status = "MOHPLAYING";
        $call_log->save();

        CallAtribute::create([
            'call_id' => $call_id,
            "attribute_name" => "QUEUETIME",
            "start_time" => now()
        ]);

        AdminDashboardHelper::call_in_ivr($queue->company_id);
    }

    public static function change_to_list($moh_files)
    {
        $url_list = "";
        foreach ($moh_files as $key => $moh_file) {
            if ($key == 0) {
                $url_list = "sound:" . $moh_file->file_url;
            } else {
                $url_list .= ",sound:" . $moh_file->file_url;
            }
        }
        return $url_list;
    }
}