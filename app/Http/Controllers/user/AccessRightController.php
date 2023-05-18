<?php

namespace App\Http\Controllers\user;

use App\Helpers\AccessChecker;
use App\Http\Controllers\Controller;
use App\Http\Resources\AccessRightParentChildStructureResource;
use App\Models\AccessProfile;
use App\Models\AccessRight;
use App\Models\RoleProfile;
use App\Models\User;
use App\Models\UserAccessProfile;
use App\Services\AccessRightService;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AccessRightController extends Controller
{
    protected $access_right_service;
    public function __construct(AccessRightService $access_right_service)
    {
        $this->access_right_service = $access_right_service;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

    function create_role_profile(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required',
            'description' => 'required',
        ]);
        $company_id = $request->user()->company_id;
        if ($company_id) {
            $duplicate_role_profile = RoleProfile::where(["name" => $validatedData["name"], "company_id" => $company_id])->first();
            if (!$duplicate_role_profile) {
                $role_profile = RoleProfile::create([
                    'name' => $validatedData['name'],
                    'description' => $validatedData['description'],
                    'company_id' => $company_id
                ]);

                \App\Helpers\LogActivity::addToLog('role profile named ' . $role_profile->name . ' created');
                return response()->json([
                    'message' => 'created successfully',
                    'role_profile' => $role_profile
                ], 200);
            } else {
                return response()->json([
                    'message' => 'role profile with the same name exist',
                ], 422);
            }
        } else {
            return response()->json([
                'message' => 'Please setup company',
            ], 422);
        }
    }

    public function edit_role_profile(Request $request)
    {
        $validatedData = $request->validate([
            'id' => 'required|integer|exists:role_profiles,id',
            'name' => 'required',
            'description' => 'required',
        ]);
        $company_id = $request->user()->company_id;
        if ($company_id) {
            $duplicate_role_profile = RoleProfile::where(["name" => $validatedData["name"], "company_id" => $company_id])->first();
            if (!$duplicate_role_profile) {
                $role_profile_to_edit = RoleProfile::find($validatedData["id"]);
                $role_profile_to_edit->name = $validatedData['name'];
                $role_profile_to_edit->description = $validatedData['description'];
                $role_profile_to_edit->save();

                \App\Helpers\LogActivity::addToLog('role profile for ' . $role_profile_to_edit->name . ' updated');
                return response()->json([
                    'message' => 'created successfully',
                    'role_profile' => $role_profile_to_edit
                ], 200);
            } else if ($duplicate_role_profile->id == $validatedData["id"]) {
                $role_profile_to_edit = RoleProfile::find($validatedData["id"]);
                $role_profile_to_edit->name = $validatedData['name'];
                $role_profile_to_edit->description = $validatedData['description'];
                $role_profile_to_edit->save();
                return response()->json([
                    'message' => 'updated successfully',
                    'role_profile' => $role_profile_to_edit
                ], 200);
            }
            return response()->json([
                'message' => 'role profile with the same name exist',
            ], 422);
        } else {
            return response()->json([
                'message' => 'Please setup company',
            ], 422);
        }
    }

    public function assign_access_to_profile(Request $request)
    {
        $validatedData = $request->validate([
            'access_name' => 'required|string|exists:access_rights,access_name',
            'role_profile_id' => 'required|exists:role_profiles,id',
        ]);
        $company_id = $request->user()->company_id;
        if ($company_id) {
            $check_duplicate = AccessProfile::where(["access_name" => $request->access_name, "role_profile_id" => $request->role_profile_id])->first();
            if (!$check_duplicate) {
                $access_profile = AccessProfile::create([
                    "access_name" => $validatedData["access_name"],
                    "role_profile_id" => $validatedData['role_profile_id'],
                    "company_id" => $company_id
                ]);
                if ($validatedData["access_name"] == "Inbound Calls" || $validatedData["access_name"] == "Click to Call" || $validatedData["access_name"] == "Outbound Calls") {
                    $this->access_right_service->add_sip_to_users_in_role_profile($validatedData["role_profile_id"]);
                }
                \App\Helpers\LogActivity::addToLog('new access' . $validatedData['access_name'] . ' added to role profile');
                return response()->json([
                    'message' => 'created successfully',
                    'access_profile' => $access_profile
                ], 200);
            } else {
                return response()->json([
                    'message' => 'access already assigned to the role profile',
                ], 422);
            }
        } else {
            return response()->json([
                'message' => 'Please setup company',
            ], 422);
        }
    }

    public function assign_access_to_profile_bulk(Request $request)
    {
        $validatedData = $request->validate([
            'role_profile_id' => 'required|exists:role_profiles,id',
        ]);
        $company_id = $request->user()->company_id;
        $error = array();
        $has_error = false;
        if ($company_id) {
            foreach ($request->access_name as $key => $access_rights) {
                $check_duplicate = AccessProfile::where(["access_name" => $access_rights, "role_profile_id" => $request->role_profile_id])->first();
                if (!$check_duplicate) {
                    $access_profile = AccessProfile::create([
                        "access_name" => $access_rights,
                        "role_profile_id" => $validatedData['role_profile_id'],
                        "company_id" => $company_id
                    ]);
                    if ($access_rights == "Inbound Calls" || $access_rights == "Click to Call" || $access_rights == "Outbound Calls") {
                        $this->access_right_service->add_sip_to_users_in_role_profile($validatedData["role_profile_id"]);
                    }
                } else {
                    $has_error = true;
                    $error[$key]["data"] = $access_rights;
                    $error[$key]["message"] = "access already assigned to the role profile";
                }
            }

            $role = RoleProfile::find($validatedData["role_profile_id"]);
            \App\Helpers\LogActivity::addToLog('multiple accesses assigned to role ' . $role->name);
            return response()->json([
                'message' => 'created successfully',
                'has_eror' => $has_error,
                "error_message" => $error
            ], 200);
        } else {
            return response()->json([
                'message' => 'Please setup company',
            ], 422);
        }
    }

    public function revoke_access_from_role_profile(Request $request)
    {
        $validatedData = $request->validate([
            'access_right' => 'required|exists:access_rights,access_name',
            'role_profile_id' => 'required|exists:role_profiles,id',
        ]);
        $company_id = $request->user()->company_id;
        $check_exist = AccessProfile::where(["access_name" => $request->access_right, "role_profile_id" => $request->role_profile_id, "company_id" => $company_id])->first();
        if ($check_exist) {
            $this->access_right_service->revoke_sip_to_users_in_role_profile($check_exist->role_profile_id);
            $access_profile = AccessProfile::find($check_exist->id);
            $access_profile->delete();
            return response()->json([
                'message' => 'access removed successfully',
            ], 200);
        } else {
            return response()->json([
                'message' => 'the role doesn\'t exist',
            ], 422);
        }
    }

    public function assign_role_profile_to_user(Request $request)
    {
        $validatedData = $request->validate([
            'role_profile_id' => 'required|exists:role_profiles,id',
        ]);
        $company_id = $request->user()->company_id;
        $error = array();
        $has_error = false;
        if ($company_id) {
            foreach ($request->user_ids as $key => $user_id) {
                $check_duplicate = UserAccessProfile::where(["user_id" => $user_id])->first();
                if (!$check_duplicate) {
                    UserAccessProfile::create([
                        "user_id" => $user_id,
                        "access_profile_id" => $request->role_profile_id,
                        "company_id" => $company_id
                    ]);
                    $is_agent = \App\Helpers\AccessChecker::check_if_agent($user_id);
                    if ($is_agent) {
                        $this->access_right_service->assign_sip($user_id);
                    } else {
                        $this->access_right_service->revoke_sip($user_id);
                    }
                } else {
                    $user_access_profile = UserAccessProfile::find($user_id);
                    $user_access_profile->access_profile_id = $request->role_profile_id;
                    $user_access_profile->save();
                    $is_agent = \App\Helpers\AccessChecker::check_if_agent($user_id);
                    if ($is_agent) {
                        $this->access_right_service->assign_sip($user_id);
                    } else {
                        $this->access_right_service->revoke_sip($user_id);
                    }
                }
            }

            $role = RoleProfile::find($validatedData["role_profile_id"]);
            \App\Helpers\LogActivity::addToLog('multiple users assigned to role ' . $role->name);
            return response()->json([
                'message' => 'created successfully',
                'has_eror' => $has_error,
                "error_message" => $error
            ], 200);
        } else {
            return response()->json([
                'message' => 'Please setup company',
            ], 422);
        }
    }


    public function revoke_role_profile_from_user(Request $request)
    {
        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_profile_id' => 'required|exists:role_profiles,id',
        ]);
        $company_id = $request->user()->company_id;
        $check_exist = UserAccessProfile::where(["user_id" => $request->user_id, "access_profile_id" => $request->role_profile_id, "company_id" => $company_id])->first();
        if ($check_exist) {
            UserAccessProfile::where(["user_id" => $request->user_id, "access_profile_id" => $request->role_profile_id])->delete();
            $is_agent = \App\Helpers\AccessChecker::check_if_agent($request->user_id);
            if ($is_agent) {
                $this->access_right_service->assign_sip($request->user_id);
            } else {
                $this->access_right_service->revoke_sip($request->user_id);
            }
            return response()->json([
                'message' => 'role removed successfully',
            ], 200);
        } else {
            return response()->json([
                'message' => 'the user or role doesn\'t exist',
            ], 422);
        }
    }

    /**
     * It gets all the access rights from the database and groups them by their parent access
     */
    public function get_access_rights()
    {
        $access_right_group = AccessRight::groupBy("parent_access")->get("parent_access");
        $access_right_list = AccessRight::get();
        return ["access_right_group" => $access_right_group, "access_right_list" => $access_right_list];
    }
    /**
     * It returns an array of two elements, the first element is an array of parent access rights and
     * the second element is an array of child access rights
     * 
     * @param Request request The request object.
     */
    public function get_formated_access_rights(Request $request)
    {
        $request->validate(["profile_id" => "required|exists:role_profiles,id"]);
        $selected_access_rights = AccessProfile::where("role_profile_id", $request->profile_id)->pluck("access_name");
        $access_right_group = AccessRight::groupBy("parent_access")->get("parent_access");
        $access_right_list = AccessRight::get();
        foreach ($access_right_group as $key => $access_right_gro) {
            $access_right_group->selected_access_rights = $selected_access_rights;
        }
         $access_right_group->selected_access_rights = $selected_access_rights;
        return ["access_right_list" => AccessRightParentChildStructureResource::collection($access_right_group),"selected_access_rights"=>$selected_access_rights];
    }

    public function get_access_rights_table()
    {
        // $access_right_group = AccessRight::get()->groupBy("parent_access")->paginate();
        $access_right_list = AccessRight::paginate();
        return ["access_right_list" => $access_right_list];
    }

    public function get_role_profile()
    {
        // $request = new Request();
        return RoleProfile::with('access_right')->where("company_id", Auth::user()->company_id)->get();
    }

    public function get_user_role_profile()
    {
        return UserAccessProfile::with("access_profile", "users")->where("company_id", Request()->user()->company_id)->get();
    }

    public function get_user_role_profile_table()
    {
        return UserAccessProfile::with("access_profile", "users")
            ->where("company_id", Auth::user()->company_id)
            ->paginate();
    }

    public function get_access_not_in_profile(Request $request)
    {
        // return AccessProfile::where("role_profile_id",$request->role_profile_id)->whereDoesntHave('access_rights')->get();
        return DB::select('select * from access_rights where access_name not in (select access_name from access_profiles where role_profile_id= ?)', [$request->role_profile_id]);
    }

    public function get_users_in_profile(Request $request)
    {
        $user_access = UserAccessProfile::with('users')->where("access_profile_id", $request->role_profile_id)->get();
        return $user_access;
    }

    public function has_access(Request $request)
    {
        // return $request;
        $request->validate([
            "access_rights" => "required|exists:access_rights,access_name"
        ]);
        $access = AccessChecker::has_user_access_right(Auth::user()->id, $request->access_rights);
        if (Auth::user()->role_profile) {
            $role_id =  Auth::user()->role_profile->id;
            $access = AccessProfile::where([
                "access_name" => $request->access_rights,
                "role_profile_id" => $role_id
            ])->first();
            if ($access) {
                return response()->json(["has_access" => true], 200);
            } else {
                return response()->json(["has_access" => false], 200);
            }
        } else {
            return response()->json(["has_access" => false], 200);
        }
    }
}
