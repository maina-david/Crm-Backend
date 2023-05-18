<?php

namespace App\Http\Controllers\user;

use App\Events\TestEvent;
use App\Helpers\AccessChecker;
use App\Helpers\AgentStatusChangedEventHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\AgentOutboundDIDMapResource;
use App\Http\Resources\CallHistoryFormatResource;
use App\Models\ActiveAgentQueue;
use App\Models\AgentBreak;
use App\Models\AgentSessionLog;
use App\Models\CDRTable;
use App\Models\DidList;
use App\Models\OutboundSipDid;
use App\Models\User;
use App\Models\UserGroup;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AgentController extends Controller
{
    public function create_break(Request $request)
    {
        $validated_data = $request->validate([
            "name" => "required|string",
            "description" => "required|string",
            "allowed_per_day" => "required|integer",
            "maximum_allowed_time" => "required|integer"
        ]);
        $company_id = $request->user()->company_id;
        $check_duplicate = AgentBreak::where(["name" => $request->name, "company_id" => $company_id])->first();
        if (!$check_duplicate) {
            AgentBreak::create([
                "name" => $request->name,
                "description" => $request->description,
                "allowed_per_day" => $request->allowed_per_day,
                "maximum_allowed_time" => $request->maximum_allowed_time,
                "status" => "ACTIVE",
                "company_id" => $company_id
            ]);
            return response()->json([
                'message' => 'successfully added',
            ], 200);
        } else {
            throw ValidationException::withMessages(["break already added"]);
        }
    }

    public function get_all_sip()
    {

        $agent_list = DB::select("SELECT DISTINCT (`active_agent_queues`.`user_id`) AS `user_id`,`users`.`name`,`active_agent_queues`.`sip_id`, `active_agent_queues`.`status`, `sip_status`, `penality`, `is_paused` FROM `active_agent_queues` INNER JOIN `user_groups` ON `user_groups`.`user_id`=`active_agent_queues`.`user_id` INNER JOIN `users` ON `users`.`id`=`active_agent_queues`.`user_id` WHERE `active_agent_queues`.company_id=" . Auth::user()->company_id);

        return $agent_list;
    }

    public function get_all_sip_modified()
    {
        return ActiveAgentQueue::with([
            'agent' => function ($query) {
                $query->groupBy('id');
            }
        ])->where("company_id", Auth::user()->company_id)
            ->get();
    }

    public function update_break(Request $request)
    {
        $validated_data = $request->validate([
            "id" => "required|exists:agent_breaks,id",
            "name" => "required|string",
            "description" => "required|string",
            "allowed_per_day" => "required|integer",
            "maximum_allowed_time" => "required|integer"
        ]);
        $company_id = $request->user()->company_id;
        $break_update = AgentBreak::find($request->id);
        if ($break_update->company_id == $company_id) {
            $break_update->name = $request->name;
            $break_update->description = $request->description;
            $break_update->allowed_per_day = $request->allowed_per_day;
            $break_update->maximum_allowed_time = $request->maximum_allowed_time;
            $break_update->save();
            return response()->json([
                'message' => 'successfully updated',
            ], 200);
        } else {
            throw ValidationException::withMessages(["break doesn't exist"]);
        }
    }

    public function update_break_status(Request $request)
    {
        $validated_data = $request->validate([
            "id" => "required|exists:agent_breaks,id"
        ]);
        $company_id = $request->user()->company_id;
        $break_update = AgentBreak::find($request->id);
        if ($break_update->company_id == $company_id) {
            $break_update->status = ($break_update->status == "ACTIVE") ? "DEACTIVATED" : "ACTIVE";
            $break_update->save();
            return response()->json([
                'message' => 'successfully updated',
            ], 200);
        } else {
            throw ValidationException::withMessages(["break doesn't exist"]);
        }
    }


    public function get_all_breaks(Request $request)
    {
        $company_id = $request->user()->company_id;
        return AgentBreak::where("company_id", $company_id)->get();
    }

    public function get_active_break(Request $request)
    {
        $company_id = $request->user()->company_id;
        return AgentBreak::where(["company_id" => $company_id, "status" => "ACTIVE"])->get();
    }

    public function take_break(Request $request)
    {
        $validated_data = $request->validate([
            "break_id" => "required|integer|exists:agent_breaks,id"
        ]);
        $company_id = $request->user()->company_id;
        $user_id = $request->user()->id;
        $todays_break = AgentSessionLog::where(["user_id" => $user_id, "break_type" => $request->break_id])->whereDate('start_time', date("Y-m-d"))->count();
        $break_data = AgentBreak::find($request->break_id);
        if ($todays_break < $break_data->allowed_per_day || $break_data->allowed_per_day == 0) {
            $check_of_unfinished_break = AgentSessionLog::where(["user_id" => $user_id, "attribute_type" => "break"])->whereDate('start_time', date("Y-m-d"))->whereNull('end_time')->count();
            if ($check_of_unfinished_break > 0) {
                throw ValidationException::withMessages(["you are already on beak"]);
            }
            AgentSessionLog::create([
                "user_id" => $user_id,
                "attribute_type" => "BREAK",
                "break_type" => $request->break_id,
                "start_time" => now()
            ]);

            ActiveAgentQueue::where("user_id", $user_id)->update(["is_paused" => true, "status" => "PAUSED"]);
            $event_response = AgentStatusChangedEventHelper::notify_agent_status($user_id);
            return response()->json([
                'message' => 'break started',
            ], 200);
        } else {
            throw ValidationException::withMessages(["you have used  your beak"]);
        }
    }

    public function resume_from_break(Request $request)
    {
        $company_id = $request->user()->company_id;
        $user_id = $request->user()->id;

        $check_of_unfinished_break = AgentSessionLog::where(["user_id" => $user_id, "attribute_type" => "break"])->whereDate('start_time', date("Y-m-d"))->whereNull('end_time')->first();
        if ($check_of_unfinished_break) {
            $update_break = AgentSessionLog::find($check_of_unfinished_break->id);
            $update_break->end_time = now();
            $update_break->save();
            ActiveAgentQueue::where("user_id", $user_id)->update(["is_paused" => false, "status" => "ONLINE"]);
            $event_response = AgentStatusChangedEventHelper::notify_agent_status($user_id);
            return response()->json([
                'message' => 'back to active session',
            ], 200);
        } else {
            throw ValidationException::withMessages(["already on active session"]);
        }
    }




    public function get_active_agents(Request $request)
    {
        $user_id = $request->user()->id;
        $queue_id = ($request->queue_id == null) ? null : " AND queue_id=" . $request->queue_id;
        $where_group = $this->where_group($user_id);

        $agent_list = DB::select("SELECT DISTINCT (`active_agent_queues`.`user_id`) AS `user_id`,`users`.`name`, `active_agent_queues`.`status`, `sip_status`, `penality`, `is_paused` FROM `active_agent_queues` INNER JOIN `user_groups` ON `user_groups`.`user_id`=`active_agent_queues`.`user_id` INNER JOIN `users` ON `users`.`id`=`active_agent_queues`.`user_id` WHERE $where_group" . $queue_id);
        foreach ($agent_list as $key => $agents) {
            $agent_list[$key]->queues = DB::select("SELECT `queues`.`name` FROM `active_agent_queues` INNER JOIN `queues` ON `queues`.`id`=`active_agent_queues`.`queue_id` WHERE `user_id`=$agents->user_id");
        }
        return $agent_list;
    }

    public function get_agent_status(Request $request)
    {
        $user_id = $request->user()->id;
        $sip_status = ActiveAgentQueue::where("user_id", $user_id)->first(["sip_status", "status", "penality"]);
        return $sip_status;
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

    public function get_agent_with_click_to_call()
    {
        return AgentOutboundDIDMapResource::collection(AccessChecker::get_users_with_this_access("Click to Call"));
    }

    public function assign_did_to_agent(Request $request)
    {
        $request->validate([
            "did" => "required|exists:did_lists,id",
            "agent_id" => "required|exists:users,id"
        ]);

        $did = DidList::find($request->did);
        $agent = User::find($request->agent_id);
        if ($agent->company_id != Auth::user()->company_id || $did->company_id != Auth::user()->company_id) {
            return response()->json(["You don't have access to the resource!"], 401);
        }
        $number_exit = OutboundSipDid::where("sip_id", $agent->sip_id)->first();
        if ($number_exit) {
            $number_exit->update(["did_id" => $request->did]);
        } else {

            OutboundSipDid::create([
                "campany_id" => Auth::user()->company_id,
                "sip_id" => $agent->sip_id,
                "did_id" => $request->did
            ]);
        }

        return response()->json(["assigned successfuly!"], 200);
    }

    public function get_call_log()
    {
        $cdr = CDRTable::where("user_id", Auth::user()->id)
            ->where("call_type", "!=", "SIP_CALL")
            ->whereDate("created_at", now())
            ->latest()
            ->get();
        return CallHistoryFormatResource::collection($cdr);
    }

    public function reset_penality(Request $request)
    {
        $request->validate([
            "user_id" => "required|exists:users,id"
        ]);

        $company_id = Auth::user()->company_id;
        $user_to_reset = User::find($request->user_id);
        if ($company_id != $user_to_reset->company_id) {
            return response()->json(["Unauthorized access"], 401);
        }
        ActiveAgentQueue::where("user_id", $request->user_id)->update(["penality" => 0]);
        AgentStatusChangedEventHelper::notify_agent_status($request->user_id);
        $sip_status = ActiveAgentQueue::where("user_id", $request->user_id)->first(["user_id", "sip_status", "status", "penality"]);
        $event_response = event(new TestEvent(strval($request->user_id), "agent_status", [
            "status" => $sip_status
        ]));
        return response()->json(["Penality reseted successfully!"], 200);
    }
}