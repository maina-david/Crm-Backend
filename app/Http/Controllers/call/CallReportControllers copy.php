<?php

namespace App\Http\Controllers\call;

use App\Http\Controllers\Controller;
use App\Models\CDRTable;
use App\Models\IVRFlow;
use App\Models\Queue;
use App\Models\QueueLog;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CallReportControllerscopy extends Controller
{
    var $from = null;
    var $to = null;
    var $user_group = null;

    public function queue_report(Request $request)
    {
        $user_id = $request->user()->id;
        $this->from = $request->from;
        $this->to = $request->to;
        $is_owner = $request->user()->is_owner;
        $group_where_queue = $this->where_queue_group($user_id, $is_owner);
        // $group_where = $this->where_group($user_id);

        $total_calls = DB::select('SELECT COUNT(`queue_logs`.`id`) as `calls`,`queue_logs`.`status` FROM `queue_logs` INNER JOIN `queues` ON `queues`.`id`=`queue_logs`.`queue_id` WHERE ' . $group_where_queue . ' GROUP BY `queue_logs`.`status`');

        $calls_per_queue_query = Queue::withCount(['queue_logs as total' => function (Builder $query) {
            $query->whereBetween('created_at', [$this->from, $this->to]);
        }, 'queue_logs as ANSWERED' => function (Builder $query) {
            $query->where('status', "ANSWERED")->whereBetween('created_at', [$this->from, $this->to]);
        }, 'queue_logs as ABANDONED' => function (Builder $query) {
            $query->where('status', "ABANDONED")->whereBetween('created_at', [$this->from, $this->to]);
        },]);

        $user_groups = UserGroup::where("user_id", $user_id)->get("group_id");

        foreach ($user_groups as $key => $user_group) {
            $calls_per_queue_query->orwhere('group_id', $user_group->group_id);
        }
        $calls_per_queue = $calls_per_queue_query->get(["id", "name"]);


        $firstcall_resolution = DB::select("SELECT ( COUNT(DISTINCT(`caller_id`)) / COUNT(`caller_id`) *100) AS `total_calls`, `queues`.`name`,date('created_at') as call_date FROM `queue_logs` INNER JOIN `queues` ON `queues`.`id`=`queue_logs`.`queue_id` WHERE  $group_where_queue GROUP BY `queues`.`name`,call_date");

        $service_level = DB::select("SELECT ( ( COUNT(`queue_logs`.`id`) /( SELECT COUNT(`queue_logs`.`id`) FROM `queue_logs` WHERE `queue_time` < 20 ) ) * 100 ) AS `service_level`, `queues`.`name`,date('created_at') as call_date FROM `queue_logs` INNER JOIN `queues` ON `queues`.`id`=`queue_logs`.`queue_id` WHERE   $group_where_queue  GROUP BY `queue_id`,call_date");


        return [
            "cumulative" => $total_calls,
            "per_queue" => $calls_per_queue,
            "firstcall_resolution" => $firstcall_resolution,
            "service_level" => $service_level
        ];
    }

    public function cdr_report(Request $request)
    {
        $user_id = $request->user()->id;
        $this->from = $request->from;
        $this->to = $request->to;
        $where_group = $this->where_group($user_id);

        $cdr_data = DB::select("SELECT `call_id`, `phone_number`, `queue_time`, `time_to_answer`, `call_time`, `hold_time`, `mute_time`, `desposition`, `created_at` AS `call_date_time` FROM `cdr_tables` WHERE `created_at` BETWEEN $this->from AND $this->to AND $where_group AND ( `desposition` = 'ANSWERED' OR `desposition` = 'ABANDONED')");
        return $cdr_data;
    }

    public function get_agent_call_report(Request $request)
    {
        $user_id = $request->user()->id;
        $this->from = $request->from;
        $this->to = $request->to;

        $group_where_queue = $this->where_queue_group($user_id);
        // $group_where = $this->where_group($user_id);

        $total_calls = DB::select('SELECT COUNT(`queue_logs`.`id`) as `calls`,`queue_logs`.`status` FROM `queue_logs` INNER JOIN `queues` ON `queues`.`id`=`queue_logs`.`queue_id` WHERE ' . $group_where_queue . ' GROUP BY `queue_logs`.`status`');


        $this->user_groups = UserGroup::where("user_id", $user_id)->get("group_id");


        $calls_per_queue_query = User::with(["user_groups"])->withCount(['queue_logs as total' => function (Builder $query) {
            $query->whereBetween('created_at', [$this->from, $this->to]);
        }, 'queue_logs as ANSWERED' => function (Builder $query) {
            $query->where('status', "ANSWERED")->whereBetween('created_at', [$this->from, $this->to]);
        }, 'queue_logs as ABANDONED' => function (Builder $query) {
            $query->where('status', "ABANDONED")->whereBetween('created_at', [$this->from, $this->to]);
        },]);


        // $calls_per_queue_query=QueueLog::with(["users"])->count([""]);  
        $calls_per_queue = $calls_per_queue_query->get(["id", "name"]);

        return [
            "cumulative" => $total_calls,
            "per_queue" => $calls_per_queue
        ];
    }

    public function get_agent_activity_report(Request $request)
    {
        $user_id = $request->user()->id;
        $this->from = $request->from;
        $this->to = $request->to;

        $where_group = $this->where_group($user_id);

        $agent_activity = DB::select("SELECT `date`, `online_time`, `break_time`, `penality`, `users`.`name` FROM `agent_statuses` INNER JOIN users ON users.id = agent_statuses.user_id INNER JOIN user_groups ON user_groups.user_id= agent_statuses.user_id WHERE " . $where_group);
        return $agent_activity;
    }

    public function get_ivr_hit_report(Request $request)
    {
        $this->from = $request->from;
        $this->to = $request->to;
        $ivr_option = $request->ivr_option;
        $ivr_flow_data = DB::select("SELECT COUNT(`call_ivr_logs`.`id`), `ivr_flows`.`flow_name` FROM `call_ivr_logs` INNER JOIN `ivr_flows` ON `ivr_flows`.`id`=`call_ivr_logs`.`next_ivr_flow` WHERE `currnt_ivr_flow`=$ivr_option GROUP BY `ivr_flows`.`flow_name`");

        return $ivr_flow_data;
    }

    public function get_ivr_background(Request $request)
    {
        $this->company_id = $request->user()->company_id;
        $back_grounds = IVRFlow::where([
            "application_type" => "Background"
        ])->whereHas("ivrs", function ($query) {
            return $query->where("company_id", "=", $this->company_id);
        })->get(["id", "flow_name"]);
        return $back_grounds;
    }

    private function where_queue_group($user_id, $is_owner = false)
    {
        $where_array = "";
        $user_groups = UserGroup::where("user_id", $user_id)->get("group_id");
        if ($is_owner)
            $user_groups = UserGroup::get("group_id");
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
