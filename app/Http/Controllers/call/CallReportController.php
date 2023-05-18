<?php

namespace App\Http\Controllers\call;

use App\Http\Controllers\Controller;
use App\Models\CallcenterSetting;
use App\Models\Group;
use App\Models\QueueLog;
use App\Models\User;
use App\Models\UserGroup;
use App\Models\UserQueue;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Type\Decimal;

class CallReportController extends Controller
{
    /*******************************Admin dashboard **********************************************************/
    public function get_total_call_inprogress(Request $request)
    {
        $user_id = $request->user()->id;
        $company_id = $request->user()->company_id;
        $group_where = $this->where_queue_group($user_id);
        $call_logs = DB::select('select count(id) as calls, status from queue_logs where ' . $group_where . ' AND (status="ONCALL" OR status="MOHPLAYING" OR status="RINGAGENT") GROUP BY status');
        $total_calls = 0;
        $calls_in_queeu = 0;
        $call_in_progress = 0;
        foreach ($call_logs as $key => $call_log) {
            if ($call_log->status == "ONCALL") {
                $call_in_progress += $call_log->calls;
                $total_calls += $call_log->calls;
            } else if ($call_log->status == "MOHPLAYING" || $call_log->status == "RINGAGENT") {
                $calls_in_queeu += $call_log->calls;
                $total_calls += $call_log->calls;
            }
        }
        $ivr_calls = DB::select("SELECT COUNT(`call_id`) AS `calls` FROM `call_logs` WHERE `company_id`=$company_id AND `call_status`='ONIVR'");
        return response()->json([
            'total_calls' => $total_calls + $ivr_calls[0]->calls,
            'calls_in_queue' => $calls_in_queeu,
            'calls_in_ivr' => $ivr_calls[0]->calls,
            'call_in_progress' => $call_in_progress
        ], 200);
    }

    public function calls_per_queue_daily(Request $request)
    {
        $user_id = $request->user()->id;
        $group_where = $this->where_queue_group($user_id);
        $kpi = $request->kpi;

        $where_status = ($kpi == "total_call") ? "" : " AND `queue_logs`.`status`='$kpi'";

        $call_logs = DB::select('SELECT COUNT(`queue_logs`.`id`) as `calls`, `queues`.`name` as `queue_name` FROM `queue_logs` INNER JOIN `queues` ON `queues`.`id`=`queue_logs`.`queue_id` WHERE date(`queue_logs`.`created_at`)=CURDATE() AND ' . $group_where . ' ' . $where_status . ' GROUP BY `queue_name`');

        return $call_logs;
    }

    public function get_queue_kpi_daily(Request $request)
    {
        $user_id = $request->user()->id;
        $group_where = $this->where_queue_group($user_id);

        $call_logs = DB::select('SELECT COUNT(`queue_logs`.`id`) AS `total_call`, AVG(`queue_time`) AS `queue_time`, AVG(`call_time`) AS `call_time`, AVG(`hold_time`) AS `hold_time`, AVG(`mute_time`) AS `mute_time`, `queues`.`name` FROM `queue_logs` INNER JOIN `queues` ON `queues`.`id`=`queue_logs`.`queue_id` WHERE date(`queue_logs`.`created_at`)=CURDATE() AND ' . $group_where . ' GROUP BY `name`');

        return $call_logs;
    }

    public function get_call_abandonment_rate_daily(Request $request)
    {
        $user_id = $request->user()->id;
        $group_queue_where = $this->where_queue_group($user_id);
        $groups_where = $this->where_group($user_id);

        $call_logs = DB::select('SELECT COUNT(`queue_logs`.`id`) as `calls`, `queues`.`name` as `queue_name`,`queue_logs`.`status` FROM `queue_logs` INNER JOIN `queues` ON `queues`.`id`=`queue_logs`.`queue_id` WHERE date(`queue_logs`.`created_at`)=CURDATE() AND' . $group_queue_where . ' GROUP BY `queue_name`,`queue_logs`.`status` ORDER BY `queue_name`');
        $queues = DB::select('SELECT * from queues where ' . $groups_where);
        $abandoned = array();

        foreach ($queues as $key => $queue) {
            $temp_array = array();
            foreach ($call_logs as $call_log) {
                if ($queue->name == $call_log->queue_name) {
                    $temp_array[$key][$call_log->status] = $call_log->calls;
                }
            }
            $abandoned[$key]["queue_name"] = $queue->name;
            try {
                $abandoned[$key]["abandoned_rate"] = ($temp_array[$key]["ABANDONED"] / ($temp_array[$key]["ABANDONED"] + $temp_array[$key]["ANSWERED"])) * 100;
            } catch (\Exception $ex) {
                $abandoned[$key]["abandoned_rate"] = 0;
            }
        }
        return $abandoned;
    }

    public function calls_per_agent_daily(Request $request)
    {
        $user_id = $request->user()->id;
        $group_where = $this->where_group($user_id);
        $kpi = $request->kpi;

        $where_status = ($kpi == "total_call") ? "" : " AND `cdr_tables`.`desposition`='$kpi'";

        $call_logs = DB::select('SELECT COUNT(`cdr_tables`.`id`) as `calls`, `users`.`name` as `agent` FROM `cdr_tables` INNER JOIN `users` ON `users`.`id`=`cdr_tables`.`user_id` WHERE `cdr_tables`.`call_date`=CURDATE() AND `cdr_tables`.`call_type`!="CLICKTOCALL" AND ' . $group_where . ' ' . $where_status . ' GROUP BY `agent`');

        return $call_logs;
    }

    public function get_agent_kpi_daily(Request $request)
    {
        $user_id = $request->user()->id;
        $group_where = $this->where_group($user_id);

        $call_logs = DB::select('SELECT COUNT(`cdr_tables`.`id`) AS `total_call`, AVG(`queue_time`) AS `queue_time`, AVG(`call_time`) AS `call_time`, AVG(`hold_time`) AS `hold_time`, AVG(`mute_time`) AS `mute_time`, AVG(`time_to_answer`) as `time_to_answer`, `users`.`name` FROM `cdr_tables` INNER JOIN `users` ON `users`.`id`=`cdr_tables`.`user_id` WHERE `cdr_tables`.`call_date`=CURDATE() AND `cdr_tables`.`call_type`!="CLICKTOCALL" AND ' . $group_where . ' GROUP BY `name`');

        return $call_logs;
    }

    public function get_agent_call_abandonment_rate_daily(Request $request)
    {
        $user_id = $request->user()->id;
        $group_queue_where = $this->where_group($user_id);
        $groups_where = $this->where_group($user_id);

        $call_logs = DB::select('SELECT COUNT(`cdr_tables`.`id`) as `calls`, `users`.`name` as `agent`,`cdr_tables`.`desposition`,`cdr_tables`.`user_id` FROM `cdr_tables` INNER JOIN `users` ON `users`.`id`=`cdr_tables`.`user_id` WHERE `cdr_tables`.`call_date`=CURDATE() AND' . $group_queue_where . ' GROUP BY `agent`,`cdr_tables`.`desposition`, `user_id` ORDER BY `agent`');
        $users = DB::select('SELECT `users`.id,`users`.`name` from user_groups INNER JOIN `users` ON `users`.`id`=`user_groups`.`user_id` where ' . $groups_where);
        $abandoned = array();
        // return $call_logs;
        foreach ($users as $key => $user) {
            $temp_array = array();
            foreach ($call_logs as $call_log) {
                if ($user->id == $call_log->user_id) {
                    $temp_array[$user->id][$call_log->desposition] = $call_log->calls;
                }
            }
            $abandoned[$key]["agent"] = $user->name;
            try {
                $abandoned[$key]["abandoned_rate"] = !array_key_exists("ANSWERED", $temp_array[$user->id]) ? 100 : ($temp_array[$user->id]["ABANDONED"] / ($temp_array[$user->id]["ABANDONED"] + $temp_array[$user->id]["ANSWERED"])) * 100;
            } catch (\Exception $ex) {
                $abandoned[$key]["abandoned_rate"] = 0;
            }
        }
        return $abandoned;
    }

    public function get_service_level_daily(Request $request)
    {
        $user_id = $request->user()->id;
        $company_id = $request->user()->company_id;
        $group_queue_where = $this->where_queue_group($user_id);
        $groups_where = $this->where_group($user_id);
        $service_level_data = CallcenterSetting::where("company_id", $company_id)->first();
        $service_level = 20;
        if ($service_level_data) {
            $service_level = $service_level_data->service_level;
        }
        // $service_level = DB::select("SELECT ( (SELECT COUNT(`queue_logs`.`id`) FROM `queue_logs` WHERE `queue_time` < $service_level AND Date(`queue_logs`.`created_at`)=CURDATE() AND $group_queue_where )  /(COUNT(`queue_logs`.`id`) ) * 100 ) AS `service_level`, `queues`.`name`  FROM `queue_logs` INNER JOIN `queues` ON `queues`.`id`=`queue_logs`.`queue_id` WHERE Date(`queue_logs`.`created_at`)=CURDATE() AND $group_queue_where  GROUP BY `queue_id`");

        $groups = Group::whereHas('group_users', function ($query) use ($user_id) {
            $query->where('user_id', $user_id);
        })->pluck('id')->toArray();
        $all_calls = QueueLog::with("queue")->whereDate("created_at", Carbon::today())
            ->whereIn("group_id", $groups)->groupBy("queue_id")->select('queue_id', DB::raw('count(*) as total'))->get();
        $with_in_service_level = QueueLog::whereIn("group_id", $groups)->groupBy("queue_id")
            ->whereDate("created_at", Carbon::today())
            ->where("queue_time", '<', $service_level)
            ->select('queue_id', DB::raw('count(*) as total'))->get()->toArray();

        // return ["all_calls" => $all_calls, "within" => $with_in_service_level, "groups" => $groups];
        $service_level_return = array();
        foreach ($all_calls as $key => $all_call) {
            $service_level_return[$key]["name"] = $all_call->queue->name;
            $service_level_return[$key]["sla"] = 100;
            for ($i = 0; $i < count($with_in_service_level); $i++) {
                if ($with_in_service_level[$i]['queue_id'] == $all_call->queue_id) {
                    $sla_number = ($all_call->total == 0) ? 100 : ($with_in_service_level[$i]["total"] / $all_call->total) * 100;

                    $service_level_return[$key]["service_level"] = number_format($sla_number, 2, '.', '');
                    $service_level_return[$key]["all_data"] = $all_call->total;
                    $service_level_return[$key]["with"] = $with_in_service_level[$i]["total"];
                    break;
                }
            }
        }
        return $service_level_return;
    }
    public function get_first_call_resolution_daily(Request $request)
    {
        $user_id = $request->user()->id;
        $company_id = $request->user()->company_id;
        $group_queue_where = $this->where_queue_group($user_id);
        $groups_where = $this->where_group($user_id);
        $firstcall_resolution = DB::select("SELECT ( COUNT(DISTINCT(`caller_id`)) / COUNT(`caller_id`) *100) AS `first_call`, `queues`.`name` FROM `queue_logs` INNER JOIN `queues` ON `queues`.`id`=`queue_logs`.`queue_id` WHERE  Date(`queue_logs`.`created_at`)=CURDATE() AND  $group_queue_where GROUP BY `queues`.`name`");
        return $firstcall_resolution;
    }

    /*******************************END Admin dashboard **********************************************************/

    /**********************************Agent dashboad ************************************************************/
    public function get_agent_progress_daily(Request $request)
    {
        $user_id = $request->user()->id;
        $call_status = DB::select("SELECT count(`id`) as `calls`, `desposition` FROM `cdr_tables` where `user_id`=$user_id AND `call_type` in ('INBOUND','AGENT_CAMPAIGN') AND `call_date`=CURDATE() GROUP BY desposition");

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

        $call_status_click_to_call = DB::select("SELECT count(`id`) as `calls`, `desposition` FROM `cdr_tables` where `user_id`=$user_id AND `call_type` in ('CLICKTOCALL') AND `call_date`=CURDATE() GROUP BY desposition");
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
        return response()->json([
            'total_calls' => $total_calls,
            'answered' => $answered,
            'abandoned' => $abandoned,
            'total_calls_click_to_call' => $total_calls_click_to_call,
            'answered_click_to_call' => $answered_click_to_call,
            'noanswer' => $abandoned_click_to_call
        ], 200);
    }

    public function agent_queue_daily(Request $request)
    {
        $user_id = $request->user()->id;
        $cdr_datas = DB::select("SELECT COUNT(`cdr_tables`.`id`) AS `total_call`, AVG(`queue_time`) AS `queue_time`, AVG(`call_time`) AS `call_time`, AVG(`hold_time`) AS `hold_time`, AVG(`mute_time`) AS `mute_time`,AVG(`time_to_answer`) as `time_to_answer`,`queues`.`name` as `queue_name` FROM cdr_tables INNER JOIN `queues` ON `queues`.`id`=`cdr_tables`.`queue_id` WHERE user_id=$user_id AND call_date=CURDATE() GROUP BY queue_name");
        $queues = User::with("queue")->find($user_id);

        $call_status = DB::select("SELECT count(`id`) as `calls`, `desposition`, `queue_id` FROM `cdr_tables` where `user_id`=$user_id AND `call_date`=CURDATE() GROUP BY desposition, queue_id ORDER BY `queue_id` ASC");

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
        // $data[count($queues->queue)]

        $cdr_click_tocall = DB::select("SELECT COUNT(`cdr_tables`.`id`) AS `total_call`, AVG(`queue_time`) AS `queue_time`, AVG(`call_time`) AS `call_time`, AVG(`hold_time`) AS `hold_time`, AVG(`mute_time`) AS `mute_time`,AVG(`time_to_answer`) as `time_to_answer`,`cdr_tables`.`call_type` as `call_type` FROM cdr_tables WHERE user_id=$user_id AND call_date=CURDATE() AND call_type='CLICKTOCALL' GROUP BY call_type");
        $call_status_click = DB::select("SELECT count(`id`) as `calls`, `desposition` FROM `cdr_tables` where `user_id`=$user_id AND `call_date`=CURDATE() AND call_type='CLICKTOCALL' GROUP BY desposition");
        // return $call_status_click;
        foreach ($cdr_click_tocall as $click_tocall) {
            $data[count($queues->queue)]["queue_name"] = "CLICK to CALL";
            $data[count($queues->queue)]["total_call"] = 0;
            $data[count($queues->queue)]["ANSWERED"] = 0;
            $data[count($queues->queue)]["ABANDONED"] = 0;
            $data[count($queues->queue)]["percentage_answered"] = 0;
            $data[count($queues->queue)]["call_time"] = $click_tocall->call_time;
            $data[count($queues->queue)]["queue_time"] = $click_tocall->queue_time;
            $data[count($queues->queue)]["hold_time"] = $click_tocall->hold_time;
            $data[count($queues->queue)]["mute_time"] = $click_tocall->mute_time;
            $data[count($queues->queue)]["wrap_up_time"] = 0;
            $data[count($queues->queue)]["time_to_answer"] = 0;

            foreach ($call_status_click as $call) {
                $data[count($queues->queue)][$call->desposition] = $call->calls;
            }
            try {
                if ($data[count($queues->queue)]["NOANSWER"] + $data[count($queues->queue)]["ANSWERED"] != 0) {
                    $data[count($queues->queue)]["percentage_answered"] = ($data[count($queues->queue)]["ANSWERED"] / ($data[count($queues->queue)]["NOANSWER"] + $data[count($queues->queue)]["ANSWERED"])) * 100;
                } else {
                    $data[count($queues->queue)]["percentage_answered"] = 0;
                }
                $data[count($queues->queue)]["total_call"] = $data[count($queues->queue)]["NOANSWER"] + $data[count($queues->queue)]["ANSWERED"];
            } catch (\Exception $ex) {
                $data[count($queues->queue)]["percentage_answered"] = 0;
            }
        }
        return $data;
    }
    /**********************************Agent dashboad ************************************************************/

    private function where_queue_group($user_id)
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

    private function where_group($user_id)
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