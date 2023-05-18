<?php

namespace App\Services;

use App\Models\Queue;
use App\Models\UserQueue;
use Illuminate\Validation\ValidationException;

class QueueService
{
    public function create_queue(Queue $queue_to_add, $company_id)
    {
        $validate_duplicate_name = Queue::where(["name" => $queue_to_add->name, "company_id" => $company_id])->first();
        if ($validate_duplicate_name) {
            throw ValidationException::withMessages(["You have a queue with the same name."]);
        }

        $queue = Queue::create([
            "name" => $queue_to_add->name,
            "description" => $queue_to_add->description,
            "group_id" => $queue_to_add->group_id,
            "company_id" => $company_id,
            "moh_id" => $queue_to_add->moh_id,
            "wrap_up_time" => $queue_to_add->wrap_up_time,
            "time_out" => $queue_to_add->time_out,
            "join_empty" => $queue_to_add->join_empty,
            "leave_when_empty" => $queue_to_add->leave_when_empty,
            "status" => "Active",
        ]);

        return $queue;
    }

    public function edit_queue(Queue $queue_to_edit, $company_id)
    {
        $validate_duplicate_name = Queue::where(["name" => $queue_to_edit->name, "company_id" => $company_id])->first();

        if ($validate_duplicate_name)
            if ($validate_duplicate_name->id != $queue_to_edit->id) {
                throw ValidationException::withMessages(["You have a queue with the same name."]);
            }
        $queue_edit = Queue::find($queue_to_edit->id);
        $queue_edit->name = $queue_to_edit->name;
        $queue_edit->description = $queue_to_edit->description;
        $queue_edit->group_id = $queue_to_edit->group_id;
        $queue_edit->company_id = $queue_to_edit->company_id;
        $queue_edit->moh_id = $queue_to_edit->moh_id;
        $queue_edit->wrap_up_time = $queue_to_edit->wrap_up_time;
        $queue_edit->time_out = $queue_to_edit->time_out;
        $queue_edit->join_empty = $queue_to_edit->join_empty;
        $queue_edit->leave_when_empty = $queue_to_edit->leave_when_empty;
        $queue_edit->save();
        return $queue_edit;
    }

    public function activate_queue($queue_id, $company_id)
    {
        $queue_exist = Queue::find($queue_id);
        if ($queue_exist)
            if ($queue_exist->company_id == $company_id) {
                $queue_exist->status = ($queue_exist->status == "Active") ? "Disabled" : "Active";
                $queue_exist->save();
                return $queue_exist;
            } else {
                throw ValidationException::withMessages(["You don't have access to the queue."]);
            }
        throw ValidationException::withMessages(["The queue doesn't exist."]);
    }

    public function assign_agents_to_queue($queue, $users, $company_id)
    {
        $error = array();
        $has_error = false;
        // return $users;
        foreach ($users as $key => $user) {
            $is_same_group = \App\Helpers\CheckGroupHelper::check_group($user, $queue->group_id);
            if (!$is_same_group) {
                $has_error = true;
                $error[$key]["data"] = $user;
                $error[$key]["message"] = "user doesn't belong to that group";
            } else {
                $check_duplicate = UserQueue::where(["user_id" => $user, "queue_id" => $queue->id])->first();
                if ($check_duplicate) {
                    $has_error = true;
                    $error[$key]["data"] = $user;
                    $error[$key]["message"] = "user has been assigned the queue";
                } else {
                    UserQueue::create([
                        'user_id' => $user,
                        'queue_id' => $queue->id,
                        'company_id' => $company_id
                    ]);
                }
            }
        }
        return [
            'message' => 'associated successfully',
            'has_eror' => $has_error,
            "error_message" => $error
        ];
    }

    public function remove_agent_queue($user_id, $queue_id)
    {
        $user_queue = UserQueue::where(["user_id" => $user_id, "queue_id" => $queue_id])->delete();
        return $user_queue;
    }
}
