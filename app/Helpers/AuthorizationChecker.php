<?php

namespace App\Helpers;

use App\Models\AccessProfile;
use App\Models\UserAccessProfile;

class AuthorizationChecker
{

    public static function check_auth($access_requested)
    {
        if (!auth()->check()) {
            return false;
        } else {
            $user_role_profile = UserAccessProfile::where('user_id', auth()->user()->id)->first();
            if (!$user_role_profile) {
                return false;
            } else {
                $access_profile = AccessProfile::where(['role_profile_id' => $user_role_profile->access_profile_id, "access_name" => $access_requested])->first();
                if (!$access_profile) {
                    return false;
                } else {
                    return true;
                }
            }
        }
    }
}
