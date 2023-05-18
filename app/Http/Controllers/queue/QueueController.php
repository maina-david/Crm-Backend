<?php

namespace App\Http\Controllers\queue;

use App\Http\Controllers\Controller;
use App\Models\MusicOnHold;
use App\Models\Queue;
use App\Models\User;
use App\Models\UserGroup;
use App\Services\QueueService;
use Auth;
use Illuminate\Http\Request;

class QueueController extends Controller
{
    public function create_queue(Request $request)
    {
        $company_id = $request->user()->company_id;
        $validated_data = $request->validate([
            "name" => "required|string",
            "description" => "required|max:255",
            "group_id" => "exists:groups,id",
            "moh_id" => "exists:music_on_holds,id",
            "wrap_up_time" => "required|integer",
            "time_out" => "required|integer",
            "join_empty" => "required|exists:yas_no_tables,name",
            "leave_when_empty" => "required|exists:yas_no_tables,name"
        ]);

        $queue_to_add = new Queue([
            "name" => $validated_data["name"],
            "description" => $validated_data["description"],
            "group_id" => $request->group_id,
            "moh_id" => $request->moh_id,
            "wrap_up_time" => $validated_data["wrap_up_time"],
            "time_out" => $validated_data["time_out"],
            "join_empty" => $validated_data["join_empty"],
            "leave_when_empty" => $validated_data["leave_when_empty"],
            "status" => "Active",
        ]);

        $new_queue = (new QueueService)->create_queue($queue_to_add, $company_id);

        return response()->json([
            'message' => 'successfully created',
            'queue' => $new_queue
        ], 200);
    }

    public function edit_queue(Request $request)
    {
        $company_id = $request->user()->company_id;
        $validated_data = $request->validate([
            "id" => "required|integer|exists:queues,id",
            "name" => "required|string",
            "description" => "required|max:255",
            "group_id" => "exists:groups,id",
            "moh_id" => "exists:music_on_holds,id",
            "wrap_up_time" => "required|integer",
            "time_out" => "required|integer",
            "join_empty" => "required|exists:yas_no_tables,name",
            "leave_when_empty" => "required|exists:yas_no_tables,name"
        ]);

        $queue_to_add = new Queue();

        $queue_to_add->id = $request->id;
        $queue_to_add->name = $request->name;
        $queue_to_add->description = $request->description;
        $queue_to_add->group_id = $request->group_id;
        $queue_to_add->company_id = $company_id;
        $queue_to_add->moh_id = $request->moh_id;
        $queue_to_add->wrap_up_time = $request->wrap_up_time;
        $queue_to_add->time_out = $request->time_out;
        $queue_to_add->join_empty = $request->join_empty;
        $queue_to_add->leave_when_empty = $request->leave_when_empty;

        $new_queue = (new QueueService)->edit_queue($queue_to_add, $company_id);

        return response()->json([
            'message' => 'successfully updated',
            'queue' => $new_queue
        ], 200);
    }

    public function activate_queue(Request $request)
    {
        $company_id = $request->user()->company_id;
        $validated_data = $request->validate([
            "id" => "required|integer|exists:queues,id",
        ]);

        $new_queue = (new QueueService)->activate_queue($request->id, $company_id);

        return response()->json([
            'message' => 'successfully changed',
            'queue' => $new_queue
        ], 200);
    }

    public function get_all_queues()
    {
        $company_id = (Request())->user()->company_id;
        return Queue::with(["group", "moh", "agents"])->where("company_id", $company_id)->get();
    }

    public function get_all_queues_table()
    {
        return Queue::with(["group", "moh", "agents"])
            ->where("company_id", Auth::user()->company_id)
            ->paginate();
    }

    public function get_agents_in_queue(Request $request)
    {
        $company_id = Auth::user()->company_id;
        $request->validate([
            "queue_id" => "required"
        ]);
        return User::whereHas('queue', function ($query) use ($request) {
            $query->where('queue_id', $request->queue_id);
        })->get();
    }

    public function get_unsigned_queues()
    {
        $company_id = (Request())->user()->company_id;
        return Queue::where(["company_id" => $company_id, 'group_id' => null])->get();
    }

    public function get_queue_by_group()
    {
        $company_id = (Request())->user()->company_id;
        $user_groups = UserGroup::where("user_id", (Request())->user()->id)->get("group_id");
        $goup_list = array();
        if ($user_groups) {
            foreach ($user_groups as $key => $user_group) {
                $goup_list[$key] = $user_group->group_id;
            }
        }
        return Queue::with(["group", "moh"])->where(["company_id" => $company_id, "group_id" => $goup_list])->get();
    }

    public function assign_agent_to_queue(Request $request)
    {
        $company_id = $request->user()->company_id;
        $validated_data = $request->validate([
            "id" => "required|integer|exists:queues,id",
        ]);
        $user_groups = UserGroup::where("user_id", (Request())->user()->id)->get("group_id");
        $goup_list = array();
        if ($user_groups) {
            foreach ($user_groups as $key => $user_group) {
                $goup_list[$key] = $user_group->group_id;
            }
        }
    }

    public function assign_agents_to_queue(Request $request)
    {
        $company_id = $request->user()->company_id;
        $validated_data = $request->validate([
            "queue_id" => "required|integer|exists:queues,id",
            "users" => "required|array"
        ]);
        // return
        $queue = Queue::where('id', $request->queue_id)->first();
        $belong_to_group = \App\Helpers\CheckGroupHelper::check_group($request->user()->id, $queue->group_id);
        if (!$belong_to_group) {
            return response()->json([
                'message' => 'unauthorized',
            ], 403);
        }

        $assigned_agents = (new QueueService())->assign_agents_to_queue($queue, $request->users, $company_id);
        return response()->json($assigned_agents, 200);
    }

    public function remove_agent_queue(Request $request)
    {
        $company_id = $request->user()->company_id;
        $validated_data = $request->validate([
            "queue_id" => "required|integer|exists:queues,id",
            "user_id" => "required|integer|exists:users,id"
        ]);
        // return
        $queue = Queue::where('id', $request->queue_id)->first();
        $belong_to_group = \App\Helpers\CheckGroupHelper::check_group($request->user()->id, $queue->group_id);
        if (!$belong_to_group) {
            return response()->json([
                'message' => 'unauthorized',
            ], 403);
        }

        $assigned_agents = (new QueueService())->remove_agent_queue($request->user_id, $request->queue_id);
        return response()->json([
            'message' => 'successfully removed',
        ], 200);
    }



    public function get_unassigned_agents(Request $request)
    {
        $queue_id = $request->id;
    }

    public function assign_moh_to_queue(Request $request)
    {
        $company_id = $request->user()->company_id;
        $validated_data = $request->validate([
            "queue_id" => "required|integer|exists:queues,id",
            "moh_id" => "required|exists:music_on_holds,id"
        ]);

        $qeueu_data = Queue::find($request->queue_id);
        if ($qeueu_data->company_id == $company_id) {
            $qeueu_data->moh_id = $request->moh_id;
            $qeueu_data->save();
            return response()->json([
                'message' => 'successfully added',
                'queue' => $qeueu_data
            ], 200);
        } else {
            return response()->json([
                'message' => 'unauthorized',
            ], 403);
        }
    }
}
