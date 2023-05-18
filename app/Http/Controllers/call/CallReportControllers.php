<?php

namespace App\Http\Controllers\call;

use App\Http\Controllers\Controller;
use App\Http\Resources\AgentActivityResource;
use App\Http\Resources\CDRResource;
use App\Models\AccessProfile;
use App\Models\AgentStatus;
use App\Models\CalendarTable;
use App\Models\CallcenterSetting;
use App\Models\CDRTable;
use App\Models\Group;
use App\Models\IVRFlow;
use App\Models\Queue;
use App\Models\QueueLog;
use App\Models\User;
use App\Models\UserAccessProfile;
use App\Models\UserGroup;
use Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Str;

class CallReportControllers extends Controller
{
    var $from = null;
    var $to = null;
    var $user_group = null;

    /**
     * It gets the queue report data from the database and returns it in a formatted way.
     * 
     * @param Request request The request object.
     */
    public function queue_report(Request $request)
    {
        /* Validating the request. */
        $validate_date = $request->validate([
            "frequency" => "required",
            "date" => "required",
            "kpi" => "required"
        ]);

        /* Getting the user id, is_owner and company_id from the user table. */
        $user_id = Auth::user()->id;
        $is_owner = Auth::user()->is_owner;
        $company_id = Auth::user()->company_id;

        $cdr_data_query = QueueLog::where("queue_logs.company_id", $company_id);

        if (Str::lower($request->frequency) == "daily") {
            $cdr_data_query->whereDate('queue_logs.created_at', $request->date)
                ->selectRaw('HOUR(`queue_logs`.`created_at`) as date');
        } else if (Str::lower($request->frequency) == "weekly") {
            $cdr_data_query->whereRaw('WEEKDAY(queue_logs.created_at)', $request->date)
                ->selectRaw("WEEKDAY(`queue_logs`.`created_at`) as date");
        } else if (Str::lower($request->frequency) == "monthly") {
            $split_date = explode("-", $request->date);
            $cdr_data_query->whereMonth('queue_logs.created_at', $split_date[1])
                ->whereYear('queue_logs.created_at', $split_date[0])
                ->selectRaw('DAY(queue_logs.created_at) as date');
        } else if (Str::lower($request->frequency) == "yearly") {
            $cdr_data_query->whereYear('queue_logs.created_at', $request->date)
                ->selectRaw('MONTH(queue_logs.created_at) as date');
        }

        if (Str::lower($request->kpi) == "answered") {
            $cdr_data_query->where('queue_logs.status', "ANSWERED")
                ->selectRaw('count(queue_logs.id) as calls');
        } else if (Str::lower($request->kpi) == "abandoned") {
            $cdr_data_query->where('queue_logs.status', "ABANDONED")
                ->selectRaw('count(queue_logs.id) as calls');
        } else if (Str::lower($request->kpi) == "totalcall") {
            $cdr_data_query->selectRaw('count(queue_logs.id) as calls');
        } else if (Str::upper($request->kpi) == "AHT") {
            $cdr_data_query->selectRaw('((AVG(`call_time`) + AVG(`hold_time`) + AVG(`mute_time`) /(COUNT(`queue_logs`.`id`)* 100))) AS `calls`');
        } else if (Str::upper($request->kpi) == "CALLTIME") {
            $cdr_data_query->selectRaw('(avg(`call_time`)) AS `calls`');
        } else if (Str::upper($request->kpi) == "HOLDTIME") {
            $cdr_data_query->selectRaw('(avg(`hold_time`)) AS `calls`');
        } else if (Str::upper($request->kpi) == "MUTETIME") {
            $cdr_data_query->selectRaw('(avg(`mute_time`)) AS `calls`');
        } else if (Str::upper($request->kpi) == "TIMETOANSWER") {
            $cdr_data_query->selectRaw('(avg(`time_to_answer`)) AS `calls`');
        } else if (Str::upper($request->kpi) == "AVGABANDONED") {
            $cdr_data_query->selectRaw('(avg(`call_time`)) AS `calls`');
        } else if (Str::upper($request->kpi) == "SERVICELEVEL") {
            $cdr_data_query->selectRaw('count(queue_logs.id) as calls');
        }

        if ($request->queue_id != null) {
            $cdr_data_query->where("queue_id", $request->queue_id);
        }

        $cdr_data = $cdr_data_query
            ->join('queues', 'queues.id', '=', 'queue_logs.queue_id')
            ->selectRaw('queues.name')
            ->groupBy(['queues.name', 'date'])
            ->get();
        $agent_list = $this->_get_queues();

        if (Str::upper($request->kpi) == "SERVICELEVEL") {
            $service_level_data = CallcenterSetting::where("company_id", $company_id)->first();
            $service_level = 20;
            if ($service_level_data) {
                $service_level = $service_level_data->service_level;
            }
            $with_in_service_level = $cdr_data_query->where("queue_time", '<', $service_level)
                ->get();
            return $cdr_data = $this->_service_level_formater($cdr_data, $with_in_service_level, $request->frequency, $agent_list, $request->date);
        }
        // return $cdr_data;
        return $this->_format_date($cdr_data, $request->frequency, $request->date, $agent_list, "queue");
    }

    /**
     * I'm trying to get the data from the database and then format it according to the frequency and
     * kpi
     * 
     * @param Request request 
     * 
     * @return <code>{
     *     "data": [
     *         {
     *             "date": "2019-01-01",
     *             "agent": [
     *                 {
     *                     "name": "Agent 1",
     *                     "calls": 0
     *                 },
     *                 {
     *                     "name": "Agent 2",
     *                     "calls": 0
     *                 },
     *                 {
     */
    public function click_to_call_report(Request $request)
    {
        /* Validating the request. */
        $validate_date = $request->validate([
            "frequency" => "required",
            "date" => "required",
            "kpi" => "required"
        ]);

        /* Getting the user id, is_owner and company_id from the user table. */
        $user_id = Auth::user()->id;
        $is_owner = Auth::user()->is_owner;
        $company_id = Auth::user()->company_id;

        $cdr_data_query = CDRTable::where("cdr_tables.company_id", $company_id)
            ->where("cdr_tables.call_type", 'CLICKTOCALL');

        if (Str::lower($request->frequency) == "daily") {
            $cdr_data_query->whereDate('cdr_tables.created_at', $request->date)
                ->selectRaw('HOUR(`cdr_tables`.`created_at`) as date');
        } else if (Str::lower($request->frequency) == "weekly") {
            $cdr_data_query->whereRaw('WEEKDAY(cdr_tables.created_at)', $request->date)
                ->selectRaw("WEEKDAY(`cdr_tables`.`created_at`) as date");
        } else if (Str::lower($request->frequency) == "monthly") {
            $split_date = explode("-", $request->date);
            $cdr_data_query->whereMonth('cdr_tables.created_at', $split_date[1])
                ->whereYear('cdr_tables.created_at', $split_date[0])
                ->selectRaw('DAY(cdr_tables.created_at) as date');
        } else if (Str::lower($request->frequency) == "yearly") {
            $cdr_data_query->whereYear('cdr_tables.created_at', $request->date)
                ->selectRaw('MONTH(cdr_tables.created_at) as date');
        }

        if (Str::lower($request->kpi) == "answered") {
            $cdr_data_query->where('cdr_tables.desposition', "ANSWERED")
                ->selectRaw('count(cdr_tables.id) as calls');
        } else if (Str::lower($request->kpi) == "noanswer") {
            $cdr_data_query->where('cdr_tables.desposition', "NOANSWER")
                ->selectRaw('count(cdr_tables.id) as calls');
        } else if (Str::lower($request->kpi) == "totalcall") {
            $cdr_data_query->selectRaw('count(cdr_tables.id) as calls');
        } else if (Str::upper($request->kpi) == "AHT") {
            $cdr_data_query->selectRaw('((AVG(`call_time`) + AVG(`hold_time`) + AVG(`mute_time`) /(COUNT(`cdr_tables`.`id`)* 100))) AS `calls`');
        } else if (Str::upper($request->kpi) == "CALLTIME") {
            $cdr_data_query->selectRaw('(avg(`call_time`)) AS `calls`');
        } else if (Str::upper($request->kpi) == "HOLDTIME") {
            $cdr_data_query->selectRaw('(avg(`hold_time`)) AS `calls`');
        } else if (Str::upper($request->kpi) == "MUTETIME") {
            $cdr_data_query->selectRaw('(avg(`mute_time`)) AS `calls`');
        }

        /* Joining the users table with the cdr_tables table and then selecting the name column from
        the users table. */
        $cdr_data = $cdr_data_query
            ->join('users', 'users.id', '=', 'cdr_tables.user_id')
            ->selectRaw('users.name')
            ->groupBy(['users.name', 'date'])
            ->get();
        $agent_list = $this->_get_agents();
        // return $cdr_data;
        return $this->_format_date($cdr_data, $request->frequency, $request->date, $agent_list, "agent");
    }

    /**
     * It takes in two arrays, one with all the calls and one with the calls that were within the service
     * level, and returns an array with the service level percentage for each agent
     * 
     * @param all_calls This is the total number of calls for each agent for each day.
     * @param with_in_service_level This is the array of data that you want to format.
     * @param frequency daily, weekly, monthly, yearly
     * @param agents array of agents
     * @param date_input The date you want to get the data for.
     */
    private function _service_level_formater($all_calls, $with_in_service_level, $frequency, $agents, $date_input)
    {
        $formated_sl_data = array();
        foreach ($all_calls as $key => $all_call) {
            foreach ($with_in_service_level as $call_in_sl) {
                if ($all_call->date == $call_in_sl->date) {
                    if ($all_call->name == $call_in_sl->name) {
                        $formated_sl_data[$key]["date"] = $call_in_sl->date;
                        $formated_sl_data[$key]["calls"] = number_format(($all_call->calls > 0) ? (($call_in_sl->calls / $all_call->calls) * 100) : 100, 2, '.', '');
                        $formated_sl_data[$key]["name"] = $call_in_sl->name;
                        break;
                    }
                }
            }
        }


        $return_array = array();

        if (Str::lower($frequency) == "yearly") {
            for ($i = 1; $i <= 12; $i++) {
                $date = date('F', mktime(0, 0, 0, $i, 10));
                $return_array["date"][$i] = $date;
            }

            foreach ($agents as $agent) {
                for ($i = 1; $i <= 12; $i++) {
                    $return_array["queue"][$agent->name][$i] = 100;
                    foreach ($formated_sl_data as $key => $cdr) {
                        if ($agent->name == $cdr["name"] && $i == $cdr["date"]) {
                            $return_array["queue"][$agent->name][$i] = number_format($cdr["calls"], 2, '.', '');
                        }
                    }
                }
            }
        } else if (Str::lower($frequency) == "monthly") {
            $day_split = explode("-", $date_input);
            for ($i = 1; $i <= cal_days_in_month(CAL_GREGORIAN, $day_split[1], $i); $i++) {
                $date = $i;
                $return_array["date"][$i] = $date;
            }

            foreach ($agents as $agent) {
                for ($i = 1; $i <= cal_days_in_month(CAL_GREGORIAN, $day_split[1], $i); $i++) {
                    $return_array["queue"][$agent->name][$i] = 100;
                    foreach ($formated_sl_data as $key => $cdr) {
                        if ($agent["name"] == $cdr["name"] && $i == $cdr["date"]) {
                            $return_array["queue"][$agent->name][$i] = number_format($cdr["calls"], 2, '.', '');
                        }
                    }
                }
            }
        } else if (Str::lower($frequency) == "weekly") {
            $dayOfWeek = array("Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday");
            foreach ($agents as $agent) {
                for ($i = 0; $i < 7; $i++) {
                    $return_array["queue"][$agent->name][$dayOfWeek[$i]] = number_format(0, 2, '.', '');
                    ;
                    foreach ($formated_sl_data as $key => $cdr) {
                        $return_array["date"][$i] = $dayOfWeek[$i];
                        if ($agent["name"] == $cdr['name'] && $i == $cdr['date']) {
                            $return_array["queue"][$agent->name][$dayOfWeek[$i]] = number_format($cdr["calls"], 2, '.', '');
                        }
                    }
                }
            }
        } else if (Str::lower($frequency) == "daily") {
            for ($i = 0; $i < 24; $i++) {
                // $return_array[sprintf("%02d", $i)] = null;
                $return_array["date"][$i] = sprintf("%02d", $i);
            }

            foreach ($agents as $agent) {
                for ($i = 1; $i <= 24; $i++) {
                    $return_array["queue"][$agent->name][$i] = 100;
                    foreach ($formated_sl_data as $key => $cdr) {
                        if ($agent["name"] == $cdr["name"] && $i == $cdr["date"]) {
                            $return_array["queue"][$agent->name][$i] = number_format($cdr["calls"], 2, '.', '');
                        }
                    }
                }
            }
        }
        return $return_array;





        return $formated_sl_data;
    }

    /**
     * It gets all the queues for the company that the user is logged in to.
     * 
     * @return A collection of queues.
     */
    private function _get_queues()
    {
        $queues = Queue::where("company_id", Auth::user()->company_id)->get();
        return $queues;
    }


    /**
     * I want to get all the CDR records between two dates, and if the user is not an owner, I want to
     * filter the records by the groups the user belongs to, and if the user has selected a queue or an
     * agent, I want to filter the records by that too.
     * 
     * @param Request request from, to, queue_id, agent_id
     * 
     * @return A collection of CDRResource
     */
    public function cdr_report(Request $request)
    {
        $request->validate([
            'from' => 'date_format:Y-m-d|required_with:to',
            'to' => 'date_format:Y-m-d|required_with:from',
        ]);

        $cdr_query = CDRTable::query()
            ->where("company_id", Auth::user()->company_id);

        if ($request->has('from') && $request->has('to')) {
            $cdr_query->whereDate('created_at', '>=', $request->from)
                ->whereDate('created_at', '<=', $request->to);
            ;
        }
        if (!Auth::user()->is_owner) {
            $groups = UserGroup::where("user_id", Auth::user()->id)->pluck('group_id');
            $cdr_query->whereIn("group_id", $groups);
        }
        if ($request->has('queue_id')) {
            $cdr_query->where("queue_id", $request->queue_id);
        }
        if ($request->has('agent_id')) {
            $cdr_query->where("user_id", $request->agent_id);
        }
        $cdr_data = $cdr_query->paginate();

        return CDRResource::collection($cdr_data);
    }

    /**
     * It gets all the users that have a role profile that is either "Inbound Calls" or "Outbound Calls"
     * and returns them.
     * 
     * @return A collection of users that have access profiles that are either "Inbound Calls" or
     * "Outbound Calls"
     */
    private function _get_agents()
    {
        $role_profiles = AccessProfile::where([
            'company_id' => Auth::user()->company_id,
            "access_name" => ["Inbound Calls", "Outbound Calls"]
        ])->pluck('role_profile_id')->toArray();

        $agents = User::where(["users.company_id" => Auth::user()->company_id])
            ->whereHas('user_access_profiles', function ($query) use ($role_profiles) {
                $query->whereIn('access_profile_id', $role_profiles);
            })
            ->get();
        return $agents;
    }

    /**
     * It takes a date, a frequency, and a kpi, and returns a formatted array of data
     * 
     * @param Request request 
     * 
     * @return <code>{
     *     "data": [
     *         {
     *             "date": "2019-01-01",
     *             "calls": 0,
     *             "name": "John Doe"
     *         },
     *         {
     *             "date": "2019-01-02",
     *             "calls": 0,
     *             "name": "John Doe"
     */
    public function get_agent_call_report(Request $request)
    {
        /* Validating the request. */
        $validate_date = $request->validate([
            "frequency" => "required",
            "date" => "required",
            "kpi" => "required"
        ]);

        /* Getting the user id, is_owner and company_id from the user table. */
        $user_id = Auth::user()->id;
        $is_owner = Auth::user()->is_owner;
        $company_id = Auth::user()->company_id;

        $cdr_data_query = CDRTable::where("cdr_tables.company_id", $company_id);

        if (Str::lower($request->frequency) == "daily") {
            $cdr_data_query->whereDate('cdr_tables.created_at', $request->date)
                ->selectRaw('HOUR(`cdr_tables`.`created_at`) as date');
        } else if (Str::lower($request->frequency) == "weekly") {
            $cdr_data_query->whereRaw('WEEKDAY(cdr_tables.created_at)', $request->date)
                ->selectRaw("WEEKDAY(`cdr_tables`.`created_at`) as date");
        } else if (Str::lower($request->frequency) == "monthly") {
            $split_date = explode("-", $request->date);
            $cdr_data_query->whereMonth('cdr_tables.created_at', $split_date[1])
                ->whereYear('cdr_tables.created_at', $split_date[0])
                ->selectRaw('DAY(cdr_tables.created_at) as date');
        } else if (Str::lower($request->frequency) == "yearly") {
            $cdr_data_query->whereYear('cdr_tables.created_at', $request->date)
                ->selectRaw('MONTH(cdr_tables.created_at) as date');
        }

        if (Str::lower($request->kpi) == "answered") {
            $cdr_data_query->where('cdr_tables.desposition', "ANSWERED")
                ->selectRaw('count(cdr_tables.id) as calls');
        } else if (Str::lower($request->kpi) == "abandoned") {
            $cdr_data_query->where('cdr_tables.desposition', "ABANDONED")
                ->selectRaw('count(cdr_tables.id) as calls');
        } else if (Str::lower($request->kpi) == "totalcall") {
            $cdr_data_query->selectRaw('count(cdr_tables.id) as calls');
        } else if (Str::upper($request->kpi) == "AHT") {
            $cdr_data_query->selectRaw('((AVG(`call_time`) + AVG(`hold_time`) + AVG(`mute_time`) /(COUNT(`cdr_tables`.`id`)* 100))) AS `calls`');
        } else if (Str::upper($request->kpi) == "CALLTIME") {
            $cdr_data_query->selectRaw('(avg(`call_time`)) AS `calls`');
        } else if (Str::upper($request->kpi) == "HOLDTIME") {
            $cdr_data_query->selectRaw('(avg(`hold_time`)) AS `calls`');
        } else if (Str::upper($request->kpi) == "MUTETIME") {
            $cdr_data_query->selectRaw('(avg(`mute_time`)) AS `calls`');
        } else if (Str::upper($request->kpi) == "TIMETOANSWER") {
            $cdr_data_query->selectRaw('(avg(`time_to_answer`)) AS `calls`');
        } else if (Str::upper($request->kpi) == "AVGABANDONED") {
            $cdr_data_query->selectRaw('(avg(`call_time`)) AS `calls`');
        }

        if ($request->queue_id != null) {
            $cdr_data_query->where("queue_id", $request->queue_id);
        }

        if ($request->call_type != null) {
            $cdr_data_query->where("call_type", $request->call_type);
        }

        $cdr_data = $cdr_data_query
            ->join('users', 'users.id', '=', 'cdr_tables.user_id')
            ->selectRaw('users.name')
            ->groupBy(['users.name', 'date'])
            ->get();
        $agent_list = $this->_get_agents();
        // return $cdr_data;
        return $this->_format_date($cdr_data, $request->frequency, $request->date, $agent_list, "agent");
    }

    /**
     * It takes a date, and a frequency, and returns an array of dates in the format of the frequency.
     * 
     * @param cdr_data array of objects
     * @param frequency daily, monthly, yearly
     * @param date_input 2014-01-01
     * 
     * @return An array of arrays.
     */
    private function _format_date($cdr_data, $frequency, $date_input, $agents, $type)
    {
        $return_array = array();

        if (Str::lower($frequency) == "yearly") {
            for ($i = 1; $i <= 12; $i++) {
                $date = date('F', mktime(0, 0, 0, $i, 10));
                $return_array["date"][$i] = $date;
            }

            foreach ($agents as $agent) {
                for ($i = 1; $i <= 12; $i++) {
                    $return_array[$type][$agent->name][$i] = 0;
                    foreach ($cdr_data as $key => $cdr) {
                        if ($agent->name == $cdr->name && $i == $cdr->date) {
                            $return_array[$type][$agent->name][$i] = number_format($cdr->calls, 2, '.', '');
                        }
                    }
                }
            }
        } else if (Str::lower($frequency) == "monthly") {
            $day_split = explode("-", $date_input);
            for ($i = 1; $i <= cal_days_in_month(CAL_GREGORIAN, $day_split[1], $i); $i++) {
                $date = $i;
                $return_array["date"][$i] = $date;
            }

            foreach ($agents as $agent) {
                for ($i = 1; $i <= cal_days_in_month(CAL_GREGORIAN, $day_split[1], $i); $i++) {
                    $return_array[$type][$agent->name][$i] = 0;
                    foreach ($cdr_data as $key => $cdr) {
                        if ($agent->name == $cdr->name && $i == $cdr->date) {
                            $return_array[$type][$agent->name][$i] = number_format($cdr->calls, 2, '.', '');
                        }
                    }
                }
            }
        } else if (Str::lower($frequency) == "weekly") {
            $dayOfWeek = array("Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday");
            foreach ($agents as $agent) {
                for ($i = 0; $i < 7; $i++) {
                    $return_array["date"][$i] = $dayOfWeek[$i];
                    $return_array[$type][$agent->name][$dayOfWeek[$i]] = number_format(0, 2, '.', '');
                    ;
                    foreach ($cdr_data as $key => $cdr) {
                        if ($agent->name == $cdr->name && $i == $cdr->date) {
                            $return_array[$type][$agent->name][$dayOfWeek[$i]] = number_format($cdr->calls, 2, '.', '');
                        }
                    }
                }
            }
        } else if (Str::lower($frequency) == "daily") {
            for ($i = 0; $i < 24; $i++) {
                // $return_array[sprintf("%02d", $i)] = null;
                $return_array["date"][$i] = sprintf("%02d", $i);
            }

            foreach ($agents as $agent) {
                for ($i = 1; $i <= 24; $i++) {
                    $return_array[$type][$agent->name][$i] = 0;
                    foreach ($cdr_data as $key => $cdr) {
                        if ($agent->name == $cdr->name && $i == $cdr->date) {
                            $return_array[$type][$agent->name][$i] = number_format($cdr->calls, 2, '.', '');
                        }
                    }
                }
            }
        }
        return $return_array;
    }

    public function get_agent_activity_report(Request $request)
    {
        $user_id = $request->user()->id;
        $from = $request->from;
        $to = $request->to;
        $agent = $request->agent;
        $is_owner = Auth::user()->is_owner;
        $user_ids = array();
        if ($is_owner) {
            $groups = Group::where("company_id", Auth::user()->company_id)->get()->pluck('id');
            $user_ids = UserGroup::whereIn("group_id", $groups)->get()->pluck('user_id');
        } else {
            $groups = UserGroup::where("user_id", Auth::user()->id)->get()->pluck('group_id');
            $user_ids = UserGroup::whereIn("group_id", $groups)->get()->pluck('user_id');
        }

        $agent_status_query = AgentStatus::WhereBetween("date", [$from, $to]);
        if ($agent != null) {
            $user_exist = UserGroup::where("user_id", $agent)->whereIn("group_id", $groups)->first();
            if ($user_exist) {
                $agent_status_query->where("user_id", $agent);
            } else {
                return response()->json(["You don't have right to access that user data"], 401);
            }
        } else {
            $agent_status_query->whereIn("user_id", $user_ids);
        }
        $agent_activity = $agent_status_query->get();

        return response()->json([
            "agent_activity" => AgentActivityResource::collection($agent_activity)->collection->groupBy('date'),
            "user_ids" => $user_ids,
            "agent" => $agent
        ]);
    }

    public function get_ivr_hit_report(Request $request)
    {
        $this->from = $request->from;
        $this->to = $request->to;
        $ivr_option = $request->ivr_option;
        $ivr_flow_data = DB::select("SELECT COUNT(`call_ivr_logs`.`id`) as `ivr_hits`, `ivr_flows`.`flow_name` FROM `call_ivr_logs` INNER JOIN `ivr_flows` ON `ivr_flows`.`id`=`call_ivr_logs`.`next_ivr_flow` WHERE `currnt_ivr_flow`=$ivr_option GROUP BY `ivr_flows`.`flow_name`");

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