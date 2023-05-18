<?php

namespace App\Console\Commands;

use App\Helpers\AdminDashboardHelper;
use App\Models\ActiveAgentQueue;
use App\Models\CallAtribute;
use App\Models\CallcenterSetting;
use App\Models\CallLog;
use App\Models\CallServer;
use App\Models\CDRTable;
use App\Models\QueueLog;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckCallInQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:call_in_queue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'WIll check calls in queue and look for available agent and assign';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // while (true) {
        $calls_in_queue = QueueLog::with("queue")->where("status", "MOHPLAYING")->orderBy('created_at')->get();
        if ($calls_in_queue) {
            foreach ($calls_in_queue as $call_in_queue) {
                $call_id = $call_in_queue->call_id;
                $queue_id = $call_in_queue->queue_id;
                $queue = $call_in_queue->queue;
                $bridge_out_id = $call_in_queue->bridge_out_id;
                $phone_number = $call_in_queue->caller_id;
                $queue_log_id = $call_in_queue->id;

                $current_time = Carbon::now();
                $queue_after = $current_time->addSecond(-($call_in_queue->queue->wrap_up_time));

                $callcenter_setting = CallcenterSetting::where("company_id", $queue->company_id)->first();
                $max_penality = 0;
                if ($callcenter_setting) {
                    $max_penality = $callcenter_setting->max_penality;
                }

                $available_agent = ActiveAgentQueue::where([
                    "queue_id" => $queue_id,
                    "status" => "ONLINE",
                    // "sip_status" => "ONLINE",
                    "is_paused" => false
                ])->whereTime("last_call_hung_up_at", '<=', $queue_after)
                    // ->where("penality", "<",$max_penality)
                    ->orderBy('last_call_hung_up_at')->first();
                if ($available_agent) {
                    $call_log = CallLog::find($call_id);
                    $call_server = CallServer::where("server_name", $call_log->source)->first();
                    $channel_out_id = \App\Helpers\CallControlHelper::call_endpoint($available_agent->sip_id, $phone_number, $call_server->server_name, $call_server->ip_address . ":" . $call_server->port);
                    CDRTable::create([
                        'call_id' => $call_id,
                        'phone_number' => $phone_number,
                        "bridge_id" => $bridge_out_id,
                        "group_id" => $queue->group_id,
                        'call_date' => now(),
                        'call_time' => 0,
                        "hold_time" => 0,
                        "mute_time" => 0,
                        'desposition' => "RINGING",
                        'sip_id' => $available_agent->sip_id,
                        "user_id" => $available_agent->user_id,
                        "queue_id" => $queue_id,
                        "company_id" => $queue->company_id,
                    ]);
                    $queue_log_to_update = QueueLog::find($queue_log_id);
                    $queue_log_to_update->status = "RINGAGENT";
                    $queue_log_to_update->sip_id = $available_agent->sip_id;
                    $queue_log_to_update->user_id = $available_agent->user_id;
                    $queue_log_to_update->channel_in_id = json_decode($channel_out_id[1])->id;
                    $queue_log_to_update->save();
                    $call_log_update = CallLog::find($call_id);
                    $call_log_update->call_status = "RINGAGENT";
                    $call_log_update->save();
                    CallAtribute::create([
                        'call_id' => $call_id,
                        'sip_id' => $available_agent->sip_id,
                        "attribute_name" => "AGENTRINGTIME",
                        "start_time" => now()
                    ]);
                    AdminDashboardHelper::agent_dashboard($available_agent->user_id);
                    ActiveAgentQueue::where("user_id", $available_agent->user_id)->update(["status" => "RINGAGENT"]);
                }
            }
        } else {
            // sleep(1);
        }
        // }
    }
}