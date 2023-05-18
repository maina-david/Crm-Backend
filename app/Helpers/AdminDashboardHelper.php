<?php

namespace App\Helpers;

use App\Events\TestEvent;
use App\Models\AccessProfile;
use App\Models\CallLog;
use App\Models\RoleProfile;
use App\Models\User;
use App\Models\UserAccessProfile;
use App\Models\UserGroup;
use Illuminate\Support\Facades\DB;

class AdminDashboardHelper
{
    public static function call_in_ivr($company_id)
    {
        ////get all admin users 

        // $role_profiles = DB::select('select `role_profile_id` from access_profiles WHERE company_id=? AND (`access_name`="Queue Agent Management" or `access_name`="Queue Management")', [$company_id]);

        $role_profiles = AccessProfile::whereIn("access_name", ["Queue Management", "Queue Agent Management", "Click to Call", "Inbound Calls", "Outbound Calls"])
            ->where("company_id", $company_id)
            ->pluck("role_profile_id");

        // $role_profile_id_string = "";
        // foreach ($role_profiles as $key => $role_profile) {
        //     if ($key == 0)
        //         $role_profile_id_string .= "(access_profile_id = " . $role_profile->role_profile_id;
        //     else
        //         $role_profile_id_string .= " OR access_profile_id = " . $role_profile->role_profile_id;
        // }
        // $role_profile_id_string .= " )";

        // $user_ids = DB::select('select * from user_access_profiles ' . $role_profile_id_string);

        $user_ids = UserAccessProfile::whereIn("access_profile_id", $role_profiles)->get();

        foreach ($user_ids as $key => $user_id) {
            $user_id_string = $user_id->user_id;
            $group_where = self::where_queue_group($user_id->user_id);
            $call_logs = DB::select('select count(id) as calls, status from queue_logs where ' . $group_where . ' AND (status="ONCALL" OR status="MOHPLAYING" OR status="RINGAGENT" OR status="CALLING") AND date(created_at)=date(now()) GROUP BY status');
            $click_tocall_data = CallLog::where(["company_id" => $company_id, "call_status" => "ONCALL", "call_type" => "CLICKTOCALL"])->count("call_id");

            $total_calls = $click_tocall_data;
            $calls_in_queeu = 0;
            $call_in_progress = $click_tocall_data;
            foreach ($call_logs as $key => $call_log) {
                if ($call_log->status == "ONCALL" || $call_log->status == "CALLING") {
                    $call_in_progress += $call_log->calls;
                    $total_calls += $call_log->calls;
                } else if ($call_log->status == "MOHPLAYING" || $call_log->status == "RINGAGENT") {
                    $calls_in_queeu += $call_log->calls;
                    $total_calls += $call_log->calls;
                }
            }
            $ivr_calls = DB::select("SELECT COUNT(`call_id`) AS `calls` FROM `call_logs` WHERE `company_id`=$company_id AND `call_status`='ONIVR'  AND date(created_at)=date(now())");


            $total_call = DB::select('SELECT COUNT(`queue_logs`.`id`) as `calls`, `queues`.`name` as `queue_name` FROM `queue_logs` INNER JOIN `queues` ON `queues`.`id`=`queue_logs`.`queue_id` WHERE date(`queue_logs`.`created_at`)=CURDATE() AND ' . $group_where . ' GROUP BY `queue_name`');

            $answered_calls = DB::select('SELECT COUNT(`queue_logs`.`id`) as `calls`, `queues`.`name` as `queue_name` FROM `queue_logs` INNER JOIN `queues` ON `queues`.`id`=`queue_logs`.`queue_id` WHERE date(`queue_logs`.`created_at`)=CURDATE() AND ' . $group_where . ' AND `queue_logs`.`status`="ANSWERED" GROUP BY `queue_name`');

            $abandoned_calls = DB::select('SELECT COUNT(`queue_logs`.`id`) as `calls`, `queues`.`name` as `queue_name` FROM `queue_logs` INNER JOIN `queues` ON `queues`.`id`=`queue_logs`.`queue_id` WHERE date(`queue_logs`.`created_at`)=CURDATE() AND ' . $group_where . ' AND `queue_logs`.`status`="ABANDONED" GROUP BY `queue_name`');

            $ivr_call = 0;
            if (!empty($ivr_calls)) {
                $ivr_call = $ivr_calls[0]->calls;
            }


            ////////////////////
            $where_group = self::where_group($user_id->user_id);

            $agent_list = DB::select("SELECT DISTINCT (`active_agent_queues`.`user_id`) AS `user_id`,`users`.`name`, `active_agent_queues`.`status`, `sip_status`, `penality`, `is_paused` FROM `active_agent_queues` INNER JOIN `user_groups` ON `user_groups`.`user_id`=`active_agent_queues`.`user_id` INNER JOIN `users` ON `users`.`id`=`active_agent_queues`.`user_id` WHERE $where_group");
            foreach ($agent_list as $key => $agents) {
                $agent_list[$key]->queues = DB::select("SELECT `queues`.`name` FROM `active_agent_queues` INNER JOIN `queues` ON `queues`.`id`=`active_agent_queues`.`queue_id` WHERE `user_id`=$agents->user_id");
            }


            $event_response = event(new TestEvent(strval($user_id->user_id), "admin_current_call", [
                'total_calls' => $total_calls + $ivr_call,
                'calls_in_queue' => $calls_in_queeu,
                'calls_in_ivr' => $ivr_call,
                'call_in_progress' => $call_in_progress,
                "total_calls_per_queue" => $total_call,
                "answered_calls" => $answered_calls,
                "abandoned" => $abandoned_calls,
                "agent_list" => $agent_list
            ]));
        }
    }

    public static function agent_dashboard($agent_id)
    {
        AgentStatusChangedEventHelper::notify_agent_status($agent_id);
        $group_where = self::where_queue_group($agent_id);


        $call_status = DB::select("SELECT count(`id`) as `calls`, `desposition` FROM `cdr_tables` where `user_id`=$agent_id AND `call_type` in ('INBOUND','AGENT_CAMPAIGN') AND `call_date`=CURDATE() GROUP BY desposition");

        $total_calls = 0;
        $answered = 0;
        $abandoned = 0;
        foreach ($call_status as $key => $call_log) {
            if ($call_log->desposition == "ANSWERED") {
                $total_calls += $call_log->calls;
                $answered = $call_log->calls;
            } else if ($call_log->desposition == "ABANDONED") {
                $total_calls += $call_log->calls;
                $abandoned = $call_log->calls;
            }
        }

        $call_status_click_to_call = DB::select("SELECT count(`id`) as `calls`, `desposition` FROM `cdr_tables` where `user_id`=$agent_id AND `call_type` in ('CLICKTOCALL') AND `call_date`=CURDATE() GROUP BY desposition");
        $total_calls_click_to_call = 0;
        $answered_click_to_call = 0;
        $abandoned_click_to_call = 0;
        foreach ($call_status_click_to_call as $key => $call_log) {
            if ($call_log->desposition == "ANSWERED") {
                $total_calls_click_to_call += $call_log->calls;
                $answered_click_to_call = $call_log->calls;
            } else if ($call_log->desposition == "NOANSWER") {
                $total_calls_click_to_call += $call_log->calls;
                $abandoned_click_to_call = $call_log->calls;
            }
        }

        $cdr_datas = DB::select("SELECT COUNT(`cdr_tables`.`id`) AS `total_call`, AVG(`queue_time`) AS `queue_time`, AVG(`call_time`) AS `call_time`, AVG(`hold_time`) AS `hold_time`, AVG(`mute_time`) AS `mute_time`,AVG(`time_to_answer`) as `time_to_answer`,`queues`.`name` as `queue_name` FROM cdr_tables INNER JOIN `queues` ON `queues`.`id`=`cdr_tables`.`queue_id` WHERE user_id=$agent_id AND call_date=CURDATE() GROUP BY queue_name");
        $queues = User::with("queue")->find($agent_id);

        $call_status = DB::select("SELECT count(`id`) as `calls`, `desposition`, `queue_id` FROM `cdr_tables` where `user_id`=$agent_id AND `call_date`=CURDATE() GROUP BY desposition, queue_id ORDER BY `queue_id` ASC");

        $data = array();
        foreach ($queues->queue as $key => $queue) {
            $data[$key]["queue_name"] = $queue->name;
            $data[$key]["total_call"] = 0;
            $data[$key]["ANSWERED"] = 0;
            $data[$key]["ABANDONED"] = 0;
            $data[$key]["percentage_answered"] = 0;
            $data[$key]["call_time"] = 0;
            $data[$key]["queue_time"] = 0;
            $data[$key]["hold_time"] = 0;
            $data[$key]["mute_time"] = 0;
            $data[$key]["wrap_up_time"] = 0;
            $data[$key]["time_to_answer"] = 0;
            foreach ($cdr_datas as $cdr_data) {
                // $data[$key]["total_call"] = $cdr_data->ans;
                if ($queue->name == $cdr_data->queue_name) {

                    $data[$key]["queue_time"] = $cdr_data->queue_time;
                    $data[$key]["call_time"] = $cdr_data->call_time;
                    $data[$key]["hold_time"] = $cdr_data->hold_time;
                    $data[$key]["mute_time"] = $cdr_data->mute_time;
                    $data[$key]["wrap_up_time"] = 0;
                    $data[$key]["time_to_answer"] = $cdr_data->time_to_answer;
                }
            }
            foreach ($call_status as $call) {
                if ($queue->id == $call->queue_id) {
                    $data[$key][$call->desposition] = $call->calls;
                }
            }
            try {
                if ($data[$key]["ABANDONED"] + $data[$key]["ANSWERED"] != 0) {
                    $data[$key]["percentage_answered"] = ($data[$key]["ANSWERED"] / ($data[$key]["ABANDONED"] + $data[$key]["ANSWERED"])) * 100;
                } else {
                    $data[$key]["percentage_answered"] = 0;
                }
            } catch (\Exception $ex) {
                $data[$key]["percentage_answered"] = 0;
            }
            $data[$key]["total_call"] = $data[$key]["ABANDONED"] + $data[$key]["ANSWERED"];
        }

        $event_response = event(new TestEvent(strval($agent_id), "agent_current_call", [
            'total_calls' => $total_calls,
            'answered' => $answered,
            'abandoned' => $abandoned,
            'total_calls_click_to_call' => $total_calls_click_to_call,
            'answered_click_to_call' => $answered_click_to_call,
            'noanswer' => $abandoned_click_to_call,
            'queue_data' => $data
        ]));
    }

    private static function where_queue_group($user_id)
    {
        $where_array = "";
        $user_groups = UserGroup::where("user_id", $user_id)->get("group_id");

        foreach ($user_groups as $key => $user_group) {
            if ($where_array == "") {
                $where_array = " (`queue_logs`.group_id=$user_group->group_id";
            } else {
                $where_array .= " OR `queue_logs`.group_id=$user_group->group_id";
            }
        }
        return $where_array . ")";
    }

    private static function where_group($user_id)
    {
        $where_array = "";
        $user_groups = UserGroup::where("user_id", $user_id)->get("group_id");

        foreach ($user_groups as $key => $user_group) {
            if ($where_array == "") {
                $where_array = " (group_id=$user_group->group_id";
            } else {
                $where_array .= " OR group_id=$user_group->group_id";
            }
        }
        return $where_array . ")";
    }
}