<?php

namespace App\Services;

use App\Models\AccessProfile;
use App\Models\AccessRight;
use App\Models\RoleProfile;
use App\Models\SipList;
use App\Models\User;
use App\Models\UserAccessGroup;
use App\Models\UserAccessProfile;
use Illuminate\Validation\ValidationException;

class AccessRightService
{
    public function add_access_right($access_right)
    {
        $access_right = AccessRight::create([
            "access_name" => $access_right->access_name,
            "access_description" => $access_right->access_description
        ]);

        \App\Helpers\LogActivity::addToLog('access right created');
        return $access_right;
    }

    public function edit_access_right($access_right)
    {
        $access_right_toupdate = AccessRight::find($access_right->id);
        $access_right_toupdate->name = $access_right->access_name;
        $access_right_toupdate->access_description = $access_right->access_description;
        $access_right_toupdate->save();
        \App\Helpers\LogActivity::addToLog('access right updated');
        return $access_right_toupdate;
    }

    public function add_role_profile($role_profile)
    {
        $role_profile_saved = RoleProfile::create([
            'name' => $role_profile->name,
            'description' => $role_profile->description,
            'company_id' => $role_profile->company_id
        ]);

        \App\Helpers\LogActivity::addToLog('role profile named ' . $role_profile->name . ' created');
        return $role_profile_saved;
    }

    public function edit_role_profile($role_profile)
    {
        $RoleProfile_find = RoleProfile::find($role_profile->id);
        $RoleProfile_find->name = $role_profile->name;
        $RoleProfile_find->description = $role_profile->description;
        $RoleProfile_find->save();

        \App\Helpers\LogActivity::addToLog('role profile for ' . $role_profile->name . ' updated');
        return $RoleProfile_find;
    }

    public function assign_access_to_role_profile($access_role_profile)
    {
        $access_role_profile_found = AccessProfile::where([
            "access_name" => $access_role_profile["access_name"],
            "role_profile_id" => $access_role_profile["role_profile_id"],
            "company_id" => $access_role_profile['company_id']
        ])->first();
        if (!$access_role_profile_found) {
            $access_profile = AccessProfile::create([
                "access_name" => $access_role_profile["access_name"],
                "role_profile_id" => $access_role_profile["role_profile_id"],
                "company_id" => $access_role_profile["company_id"]
            ]);
            if ($access_role_profile["access_name"] == "Inbound Calls" || $access_role_profile["access_name"] == "Click to Call" || $access_role_profile["access_name"] == "Outbound Calls") {
                return  $this->add_sip_to_users_in_role_profile($access_role_profile["role_profile_id"]);
            }
            \App\Helpers\LogActivity::addToLog('new access' . $access_role_profile['access_name'] . ' added to role profile');
            return $access_profile;
        } else
            throw ValidationException::withMessages(["access right already assigned"]);
    }

    public function revoke_access_profile($access_role_profile)
    {

        $access_profile_exist = AccessProfile::where([
            "access_name" => $access_role_profile["access_name"],
            "role_profile_id" => $access_role_profile["role_profile_id"],
            "company_id" => $access_role_profile['company_id']
        ])->first();
        if ($access_profile_exist) {
            $access_profile = AccessProfile::where([
                "access_group_id" => $access_profile_exist["access_group_id"],
                "access_right_id" => $access_profile_exist["access_right_id"],
                "company_id" => $access_role_profile['company_id']
            ])->delete();

            if ($access_role_profile["access_name"] == "Inbound Calls" || $access_role_profile["access_name"] == "Click to Call" || $access_role_profile["access_name"] == "Outbound Calls") {
                return  $this->revoke_sip_to_users_in_role_profile($access_role_profile["role_profile_id"]);
            }
            \App\Helpers\LogActivity::addToLog('access ' . $access_role_profile['access_name'] . ' removed from role profile');
            return $access_profile;
        } else
            throw ValidationException::withMessages(["access right doesn't exist"]);
    }

    public function assign_user_access_profile($user_access_profile)
    {
        $user_access_exist = UserAccessProfile::where([
            "user_id" => $user_access_profile["user_id"]
        ])->first();
        if (!$user_access_exist) {
            $user_acces_profile = UserAccessProfile::create([
                "user_id" => $user_access_profile["user_id"],
                "access_profile_id" => $user_access_profile["access_profile_id"],
                "company_id" => $user_access_profile['company_id']
            ]);

            $user = User::find($user_access_profile["user_id"]);
            $role = RoleProfile::find($user_access_profile["access_profile_id"]);
            \App\Helpers\LogActivity::addToLog('user ' . $user->name . 'assigned role of ' . $role->name);

            $is_agent = \App\Helpers\AccessChecker::check_if_agent($user_access_profile["user_id"]);
            if ($is_agent) {
                $this->assign_sip($user_access_profile["user_id"]);
            } else {
                $this->revoke_sip($user_access_profile["user_id"]);
            }
            return $user_acces_profile;
        } else
            throw ValidationException::withMessages(["user alerady assigned to group"]);
    }

    public function revoke_user_access_profile($user_access_profile)
    {
        $user_access_profile_exist = UserAccessProfile::where([
            "user_id" => $user_access_profile["user_id"]
        ])->first();
        if ($user_access_profile_exist) {
            $user_acces_profile = UserAccessProfile::where([
                "access_group_id" => $user_access_profile["access_group_id"],
                "user_id" => $user_access_profile["user_id"]
            ])->delete();

            $user = User::find($user_access_profile["user_id"]);
            \App\Helpers\LogActivity::addToLog('role has been removed from user ' . $user->name);
            return $user_acces_profile;
        } else
            throw ValidationException::withMessages(["user group access doesn't exist"]);
    }

    public function update_user_access_profile($user_access_profile)
    {
        $user_access_profile_exist = UserAccessProfile::where([
            "user_id" => $user_access_profile["user_id"]
        ])->first();
        if ($user_access_profile_exist) {
            $user_access_profile_exist->$user_access_profile["access_group_id"];
            $user_access_profile_exist->save();

            $user = User::find($user_access_profile["user_id"]);
            \App\Helpers\LogActivity::addToLog('role has been chaned for user ' . $user->name);
            return $user_access_profile_exist;
        } else
            throw ValidationException::withMessages(["user group access doesn't exist"]);
    }


    public function assign_sip($user_id)
    {
        $check_existing_sip = User::whereHas("sip")->find("user_id");
        if (!$check_existing_sip) {
            $available_sip = SipList::where("user_id", null)->first();
            if ($available_sip) {
                $user = User::find($user_id);
                $sip_update = SipList::find($available_sip->id);
                $sip_update->user_id = $user->id;
                $sip_update->save();
                $user->sip_id = $sip_update->id;
                $user->save();
                \App\Helpers\LogActivity::addToLog('new with sip id' . $sip_update->name . ' added to user' . $user->name);
                return $sip_update;
            }
        }
    }

    public function revoke_sip($user_id)
    {
        $check_existing_sip = User::whereHas("sip")->find("user_id");
        if ($check_existing_sip) {
            $available_sip = SipList::where("user_id", $user_id)->first();
            $user = User::find($user_id);
            $sip_update = SipList::find($available_sip->id);
            $sip_update->user_id = null;
            $sip_update->save();
            $user->sip_id = null;
            $user->save();
            \App\Helpers\LogActivity::addToLog('sip with id' . $sip_update->name . ' removed from user' . $user->name);
            return $sip_update;
        }
    }

    public function add_sip_to_users_in_role_profile($role_profile_id)
    {
        $user_roles = UserAccessProfile::where("access_profile_id", $role_profile_id)->get();
        foreach ($user_roles as $key => $user_role) {
            $this->assign_sip($user_role->user_id);
        }
    }

    public function revoke_sip_to_users_in_role_profile($role_profile_id)
    {
        $user_roles = UserAccessProfile::where("access_profile_id", $role_profile_id)->get();
        foreach ($user_roles as $key => $user_role) {
            $is_agent = \App\Helpers\AccessChecker::check_if_agent($user_role->user_id);
            if (!$is_agent)
                $this->revoke_sip($user_role->user_id);
        }
    }
}
