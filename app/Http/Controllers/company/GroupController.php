<?php

namespace App\Http\Controllers\company;

use App\Http\Controllers\Controller;
use App\Models\AccessProfile;
use App\Models\Group;
use App\Models\Queue;
use App\Models\User;
use App\Models\UserGroup;
use App\Services\GroupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class GroupController extends Controller
{
    protected $group_service;
    public function __construct()
    {
        $this->group_service = new GroupService();
    }

    public function create_group(Request $request)
    {
        $validated_data = $request->validate([
            "name" => 'required|string',
            "description" => "required|string"
        ]);
        $check_duplicate = Group::where(["name" => $request->name, "company_id" => $request->user()->company_id])->first();
        if (!$check_duplicate) {
            $group_save = new Group();
            $group_save->name = $request->name;
            $group_save->description = $request->description;
            $group_save->company_id = $request->user()->company_id;
            $group = $this->group_service->create_group($group_save);
            return response()->json([
                'message' => 'created successfully',
                'group' => $group
            ], 200);
        } else {
            return response()->json([
                'message' => 'group with the same name exist',
            ], 422);
        }
    }

    public function update_group(Request $request)
    {
        $validated_data = $request->validate([
            "id" => 'required|exists:groups,id',
            "name" => 'required|string',
            "description" => "required|string"
        ]);

        $check_duplicate = Group::where(["name" => $request->name, "company_id" => $request->user()->company_id])->first();
        // return $check_duplicate;
        if (!$check_duplicate) {
            $group_save = new Group();
            $group_save->id = $request->id;
            $group_save->name = $request->name;
            $group_save->description = $request->description;
            $group_save->company_id = $request->user()->company_id;
            $group = $this->group_service->update_group($group_save);
            return response()->json([
                'message' => 'created successfully',
                'group' => $group
            ], 200);
        } else if ($check_duplicate->id == $validated_data["id"]) {
            $group_save = new Group();
            $group_save->id = $request->id;
            $group_save->name = $request->name;
            $group_save->description = $request->description;
            $group_save->company_id = $request->user()->company_id;
            $group = $this->group_service->update_group($group_save);
            return response()->json([
                'message' => 'created successfully',
                'group' => $group
            ], 200);
        } else {
            return response()->json([
                'message' => 'group with the same name exist',
            ], 422);
        }
    }

    public function assign_users_to_group(Request $request)
    {
        $validated_data = $request->validate([
            "group_id" => 'required|exists:groups,id',
            "user_ids" => 'required|array'
        ]);

        $company_id = $request->user()->company_id;
        $error = array();
        $has_error = false;

        foreach ($request->user_ids as $key => $user_id) {
            $check_duplicate = UserGroup::where(['group_id' => $request->group_id, 'user_id' => $user_id])->first();
            $user_exist = User::find($user_id);
            if (!$check_duplicate) {
                if ($user_exist) {
                    UserGroup::create([
                        "user_id" => $user_id,
                        "group_id" => $request->group_id,
                        "company_id" => $company_id
                    ]);
                } else {
                    $has_error = true;
                    $error[$key]["data"] = $user_id;
                    $error[$key]["message"] = "user doesn't exist";
                }
            } else {
                $has_error = true;
                $error[$key]["data"] = User::find($user_id)->email;
                $error[$key]["message"] = "already assigned to the group";
            }
        }

        $group = group::find($validated_data["group_id"]);
        \App\Helpers\LogActivity::addToLog('multiple users assigned to role ' . $group->name);
        return response()->json([
            'message' => 'created successfully',
            'has_eror' => $has_error,
            "error_message" => $error
        ], 200);
    }

    public function get_all_groups()
    {
        $request = Request();
        $company_id = $request->user()->company_id;
        return Group::with("users", "queues", "chatQueues")->where("company_id", $company_id)->get();
    }

    public function remove_user_from_group(Request $request)
    {
        $validated_data = $request->validate([
            "group_id" => 'required|exists:groups,id',
            "user_id" => 'required|exists:users,id'
        ]);

        $company_id = $request->user()->company_id;

        $user_group_exist = UserGroup::where(["group_id" => $request->group_id, "user_id" => $request->user_id, "company_id" => $company_id])->first();
        if ($user_group_exist) {
            UserGroup::where(["group_id" => $request->group_id, "user_id" => $request->user_id, "company_id" => $company_id])->delete();
            $group = Group::find($request->group_id);
            $user = User::find($request->user_id);
            \App\Helpers\LogActivity::addToLog('Group ' . $group->name . ' removed from user ' . $user->name);
            return response()->json([
                'message' => 'removed successfully'
            ], 200);
        } else {
            return response()->json([
                'message' => 'user removed from the group',
            ], 422);
        }
    }



    public function get_agents_in_group(Request $request)
    {
        $company_id = (Request())->user()->company_id;
        $user_id = (Request())->user()->id;
        $belong_to_group = \App\Helpers\CheckGroupHelper::check_group($user_id, $request->group_id);
        if (!$belong_to_group) {
            return response()->json([
                'message' => 'unauthorized',
            ], 403);
        }

        $role_profiles = AccessProfile::where(["access_name" => ["Inbound Calls", "Outbound Calls"], "company_id" => $company_id])->get();
        $where_cluase = "";
        foreach ($role_profiles as $key => $role_profile) {
            if ($where_cluase == "") {
                $where_cluase = " where  access_profile_id=$role_profile->role_profile_id";
            } else {
                $where_cluase .= " OR access_profile_id=$role_profile->role_profile_id";
            }
        }
        // $users

        $agents = DB::select('select id, name, email, phone_number from users where users.status="ACTIVE" AND users.id in (select user_id from user_access_profiles ' . $where_cluase . ')');
        return response()->json([
            'agents' => $agents,
        ], 200);
    }

    public function assign_queue_group(Request $request)
    {
        $validated_data = $request->validate([
            "group_id" => 'required|exists:groups,id',
            'queues' => 'required|array'
        ]);
        $group = Group::find($request->group_id);
        $error = array();
        $has_error = false;
        if ($group->company_id == $request->user()->company_id) {
            foreach ($request->queues as $key => $queue) {
                $queue_update = Queue::find($queue);
                if ($queue_update) {
                    if ($queue_update->company_id == $request->user()->company_id) {
                        $queue_update->group_id = $request->group_id;
                        $queue_update->save();
                    } else {
                        return response()->json([
                            'message' => 'unauthorized',
                        ], 403);
                    }
                } else {
                    $has_error = true;
                    $error[$key]["data"] = $queue;
                    $error[$key]["message"] = "the queue doesn't exist";
                }
            }
            return response()->json([
                'message' => 'associated successfully',
                'has_eror' => $has_error,
                "error_message" => $error
            ], 200);
        } else {
            return response()->json([
                'message' => 'unauthorized',
            ], 403);
        }
    }

    public function remove_group_from_queue(Request $request)
    {
        $company_id = $request->user()->company_id;
        $validated_data = $request->validate([
            'queue_id' => 'required|exists:queues,id'
        ]);
        $queue = Queue::find($request->queue_id);
        if ($queue->company_id == $company_id) {
            $queue->group_id = null;
            $queue->save();
            return response()->json([
                'message' => 'association removed',
            ], 200);
        } else {
            return response()->json([
                'message' => 'unauthorized',
            ], 403);
        }
    }

    public function get_agents(Request $request)
    {
        $company_id = (Request())->user()->company_id;
        // $user_id = (Request())->user()->id;
        // $belong_to_group = \App\Helpers\CheckGroupHelper::check_group($user_id, $request->group_id);
        // if (!$belong_to_group) {
        //     return response()->json([
        //         'message' => 'unauthorized',
        //     ], 403);
        // }

        $role_profiles = AccessProfile::where(["access_name" => ["Inbound Calls", "Outbound Calls"]])->get();
        $where_cluase = "";
        foreach ($role_profiles as $key => $role_profile) {
            if ($where_cluase == "") {
                $where_cluase = "access_profile_id=$role_profile->role_profile_id";
            } else {
                $where_cluase .= " OR access_profile_id=$role_profile->role_profile_id";
            }
        }
        // $users

        $agents = DB::select('select id, name, email, phone_number from users where users.status="ACTIVE" AND users.id in (select user_id from user_access_profiles  where company_id=' . $company_id . ' AND (' . $where_cluase . '))');
        return response()->json([
            'agents' => $agents,
            'query' => 'select id, name, email, phone_number from users where users.status="ACTIVE" AND (users.id in (select user_id from user_access_profiles  where company_id=' . $company_id . ' AND (' . $where_cluase . '))'
        ], 200);
    }
}