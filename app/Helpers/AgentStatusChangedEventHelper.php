<?php
namespace App\Helpers;

use App\Events\TestEvent;
use App\Models\AccessProfile;
use App\Models\User;
use App\Models\UserAccessProfile;
use Auth;
use DB;

class AgentStatusChangedEventHelper
{
    public static function notify_agent_status($user_id)
    {
        $user_actioned = User::find($user_id);
        if ($user_actioned) {
            $company_id = $user_actioned->company_id;
            $role_profiles = AccessProfile::whereIn("access_name", ["Queue Management", "Queue Agent Management", "Click to Call", "Inbound Calls", "Outbound Calls"])
                ->where("company_id", $company_id)
                ->pluck("role_profile_id");

            $user_ids = UserAccessProfile::whereIn("access_profile_id", $role_profiles)->get();
            logger($user_ids);
            foreach ($user_ids as $user) {
                $agent_list = DB::select("SELECT DISTINCT (`active_agent_queues`.`user_id`) AS `user_id`,`users`.`name`,`active_agent_queues`.`sip_id`, `active_agent_queues`.`status`, `sip_status`, `penality`, `is_paused` FROM `active_agent_queues` INNER JOIN `user_groups` ON `user_groups`.`user_id`=`active_agent_queues`.`user_id` INNER JOIN `users` ON `users`.`id`=`active_agent_queues`.`user_id` WHERE `active_agent_queues`.company_id=" . $company_id);

                foreach ($agent_list as $key => $agents) {
                    $agent_list[$key]->queues = DB::select("SELECT `queues`.`name` FROM `active_agent_queues` INNER JOIN `queues` ON `queues`.`id`=`active_agent_queues`.`queue_id` WHERE `user_id`=$agents->user_id");
                }

                $event_response = event(new TestEvent(strval($user->user_id), "agent_current_status", [
                    'agent_list' => $agent_list
                ]));
                // return $event_response;
            }
        }
    }
}