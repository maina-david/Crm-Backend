<?php

namespace App\Helpers;

use App\Models\AccessProfile;
use App\Models\Account;
use App\Models\AccountTypeGroup;
use App\Models\RoleProfile;
use App\Models\User;
use App\Models\UserAccessProfile;
use App\Models\UserGroup;
use Auth;
use Illuminate\Support\Facades\DB;

/* It checks if a user has access to a certain account type. */

class AccessChecker
{
    /**
     * "If the user has a role profile, and that role profile has access to outbound, inbound, or click
     * to call, then return true."
     * </code>
     * 
     * @param user_id the user id of the user you want to check
     */
    public static function check_if_agent($user_id)
    {
        $role_profile = UserAccessProfile::where("user_id", $user_id)->first();
        if ($role_profile) {
            $result = AccessProfile::where('access_name', "Outbound Calls")
                ->orwhere('access_name', "Inbound Calls")
                ->orwhere('access_name', "Click to Call")
                ->Where('role_profile_id', $role_profile->access_profile_id)
                ->first();
            if ($result)
                return true;
        }
        return false;
    }

    /**
     * It returns a list of access profiles for a user if the user has the access profile of 'Customer
     * Account Managers' or 'Sales Manager'
     * 
     * @param user_id The user id of the user you want to check
     */
    public static function has_account_aprove_access($user_id)
    {
        $role_profile = UserAccessProfile::where("user_id", $user_id)->first();
        if ($role_profile) {
            $result = DB::select(" select * from `access_profiles` where `role_profile_id` = " . $role_profile->access_profile_id . " AND (`access_name` = 'Customer Account Managers' or `access_name` = 'Sales Manager' )");
            if (!empty($result))
                return $result;
        }
        return false;
    }

    /**
     * If the user has a role profile, and that role profile has access to Sales Users, Customer
     * Service User, or Customer Account User, then return true.
     * </code>
     * 
     * @param user_id The user id of the user who is trying to create the account
     */
    public static function has_account_create_access($user_id)
    {
        $role_profile = UserAccessProfile::where("user_id", $user_id)->first();
        if ($role_profile) {
            $result = DB::select(" select * from `access_profiles` where `role_profile_id` = " . $role_profile->access_profile_id . " AND (`access_name` = 'Sales Users' or `access_name` = 'Customer Service User' OR `access_name` = 'Customer Account User')");
            if (!empty($result))
                return true;
        }
        return false;
    }

    /**
     * It returns all users with the same access profile as the user with the given user_id.
     * </code>
     * 
     * @param user_id The user id of the user you want to get the similar access users for.
     * 
     * @return A collection of UserAccessProfile objects.
     */
    public static function get_users_with_similar_access($user_id)
    {
        $role_profile = UserAccessProfile::where("user_id", $user_id)->first();
        $users_same_access = UserAccessProfile::with("users")->where("access_profile_id", $role_profile->access_profile_id)->get();
        return $users_same_access;
    }

    /**
     * It gets the account types for a user based on the user's group
     * 
     * @param user_id The user id of the user you want to get the account types for.
     * 
     * @return An array of objects.
     */
    public function get_account_types($user_id)
    {
        $user_group = UserGroup::where("user_id", $user_id)->get("group_id");
        $group_string = "";

        foreach ($user_group as $key => $group) {
            if ($group_string == "") {
                $group_string = " (group_id=" . $group->group_id;
            } else {
                $group_string .= " AND group_id=" . $group->group_id;
            }
        }

        $group_string .= ")";

        $account_type = DB::select('SELECT * FROM account_types WHERE id IN( SELECT accounttype_id FROM account_type_groups WHERE ' . $group_string . ')');
        return $account_type;
    }

    /**
     * It returns an array of account types that a user has access to
     * 
     * @param user_id The user id of the user you want to get the account types for.
     * 
     * @return <code>Collection {#841 ▼
     *   #items: array:2 [▼
     *     0 =&gt; 1
     *     1 =&gt; 2
     *   ]
     * }
     * </code>
     */
    public function get_account_types_update($user_id)
    {
        $user_group = UserGroup::where("user_id", $user_id)->get()->pluck('group_id');
        $account_type = AccountTypeGroup::whereIn("group_id", $user_group)->pluck('accounttype_id');
        return $account_type;
    }

    /**
     * It checks if a user has access to an account type
     * 
     * @param user_id The user id of the user you want to check
     * @param account_type_id The account type id
     * 
     * @return A boolean value.
     */
    public static function check_has_group_access_account_type($user_id, $account_type_id)
    {
        $group_ids = UserGroup::where("user_id", $user_id)->get("group_id");
        // return $group_id;
        $account_type_groups = AccountTypeGroup::where("accounttype_id", $account_type_id)->get("group_id");
        $has_access = false;

        foreach ($group_ids as $group_id) {
            foreach ($account_type_groups as $account_type_group) {
                if ($group_id->group_id == $account_type_group->group_id) {
                    $has_access = true;
                }
            }
        }
        return $has_access;
    }

    /**
     * If the user has a role profile, and the user has the access profile, then return true
     * 
     * @param user_id The user id of the user you want to check
     */
    public static function has_KB_approve_access($user_id)
    {
        $role_profile = UserAccessProfile::where("user_id", $user_id)->first();
        if ($role_profile) {
            $result = AccessProfile::where(["role_profile_id" => $role_profile->access_profile_id, "access_name" => "knowledge_base_approve"])->first();
            if (!empty($result))
                return true;
        }
        return false;
    }

    /**
     * If the user has a role profile, and that role profile has access to create knowledge base
     * articles, then return true
     * 
     * @param user_id The user id of the user you want to check
     */
    public static function has_KB_create_access($user_id)
    {
        $role_profile = UserAccessProfile::where("user_id", $user_id)->first();
        if ($role_profile) {
            $result = AccessProfile::where(["access_name" => "knowledge_base_create", "role_profile_id" => $role_profile->access_profile_id])->first();
            if (!empty($result))
                return true;
        }
        return false;
    }


    /**
     * It returns all the users that have a specific access right
     * 
     * @param access_right the access right you want to check for
     * 
     * @return A collection of users.
     */
    public static function get_users_with_this_access($access_right)
    {
        $company_id = Auth::user()->company_id;
        $access_profile = AccessProfile::where([
            "company_id" => $company_id,
            "access_name" => $access_right
        ])->pluck("role_profile_id");
        $user_access = UserAccessProfile::with("users")->whereIn("access_profile_id", $access_profile)->get();
        $users = array();
        foreach ($user_access as $key => $user) {
            $users[] = $user;
        }
        return $users;
    }

    public static function has_user_access_right($user_id, $access_right)
    {
        $user = User::with("role_profile")->find($user_id);
        return $user;
    }
}
