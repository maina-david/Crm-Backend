<?php

namespace App\Console\Commands;

use App\Models\AgentSessionLog;
use App\Models\AgentStatus;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SessionCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:session';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'it will check sessions at the end of the day and create new session instance for the next day';

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
        $agents_not_logged_out = AgentSessionLog::where([
            "attribute_type" => "LOGIN",
            "end_time" => null
        ])->get();

        foreach ($agents_not_logged_out as $key => $agent_status) {
            $login_time_attribute = AgentSessionLog::where([
                "user_id" => $agent_status->user_id,
                "attribute_type" => "LOGIN",
                "end_time" => null
            ])->first();

            $timeFirst  = strtotime($login_time_attribute->start_time);
            $timeSecond = strtotime(now());
            $logged_in_for = $timeSecond - $timeFirst;
            $total_logged_time = $agent_status->online_time + $logged_in_for;
            AgentSessionLog::where("id", $login_time_attribute->id)->update(["end_time" => now()]);
            $break_time = 0;
            $break_attributes = AgentSessionLog::where([
                "attribute_type" => "BREAK",
                "user_id" => $agent_status->user_id,
            ])
                ->whereDate('start_time', '<=', $login_time_attribute->start_time)->get();
            foreach ($break_attributes as $key => $break_attribute) {
                if ($break_attribute->end_time == null) {
                    AgentSessionLog::where("id", $break_attribute->id)->update(["end_time" => now()]);
                }
                $timeFirst  = strtotime($break_attribute->start_time);
                $timeSecond = ($break_attribute->end_time == null) ? strtotime(now()) : strtotime($break_attribute->end_time);
                $break_time += $timeSecond - $timeFirst;
            }
            $agent_status_update=AgentStatus::where("user_id",$agent_status->user_id)->orderBy("id","DESC")->first();

            AgentStatus::where("id", $agent_status_update->id)->update([
                "logged_out_at" => now(),
                "online_time" => $total_logged_time,
                "break_time" => $break_time,
                "sip_status" => "LOGEDOUT",
                "call_status" => "LOGEDOUT"
            ]);
            AgentStatus::create([
                "date" =>  Carbon::tomorrow(),
                "user_id" =>$agent_status->user_id,
                "logged_in_at" =>Carbon::tomorrow(),
                "sip_status" => "LOGEDIN",
                "call_status" => "LOGEDIN"
            ]);
            AgentSessionLog::create([
                "user_id"=>$agent_status->user_id,
                "attribute_type"=>"LOGIN",
                "start_time"=>Carbon::tomorrow()
            ]);
        }
    }
}
