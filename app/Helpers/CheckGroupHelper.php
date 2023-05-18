<?php

namespace App\Helpers;

use App\Models\UserGroup;

class CheckGroupHelper
{
    public static function check_group($user_id, $group_id)
    {
        $user_group = UserGroup::where(['user_id' => $user_id, "group_id" => $group_id])->first();
        if ($user_group)
            return true;
        else
            return false;
    }
}
