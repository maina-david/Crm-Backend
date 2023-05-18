<?php

namespace App\Services;

use App\Models\Group;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Validation\ValidationException;

class GroupService
{
    public function create_group(Group $group)
    {
        $check_duplicate = Group::where(["name" => $group->name, "company_id" => $group->company_id])->first();
        if (!$check_duplicate) {
            $group = Group::create([
                "name" => $group->name,
                "description" => $group->description,
                "company_id" => $group->company_id
            ]);
            \App\Helpers\LogActivity::addToLog('new group with name ' . $group->name . ' created');
            return $group;
        } else {
            throw ['message' => 'group with the same name exist'];
        }
    }

    public function update_group(Group $group)
    {
        $check_duplicate = Group::where(["name" => $group->name, "company_id" => $group->company_id])->first();
        // return [$check_duplicate, $group];
        if (!$check_duplicate) {
            $group_to_update = Group::find($group->id);
            $group_to_update->name = $group->name;
            $group_to_update->description = $group->description;
            $group_to_update->save();
        } else if ($check_duplicate->id == $group["id"]) {
            $group_to_update = Group::find($group->id);
            $group_to_update->name = $group->name;
            $group_to_update->description = $group->description;
            $group_to_update->save();
        } else {
            throw ['message' => 'group with the same name exist'];
        }
        \App\Helpers\LogActivity::addToLog('new group with name ' . $group->name . ' created');
        return $group_to_update;
    }

    public function assign_group_user($user_id, $group_id, $company_id)
    {
        $check_duplicate = UserGroup::where(['group_id' => $group_id, 'user_id' => $user_id])->first();
        $user_exist = User::find($user_id);
        if (!$check_duplicate) {
            if ($user_exist) {
                $user_group = UserGroup::create([
                    "user_id" => $user_id,
                    "group_id" => $group_id,
                    "company_id" => $company_id
                ]);
                return $user_group;
            } else {
                throw ValidationException::withMessages(["user doesn't exist"]);
            }
        } else {
            throw ValidationException::withMessages(["group already assigned"]);
        }
    }
}
