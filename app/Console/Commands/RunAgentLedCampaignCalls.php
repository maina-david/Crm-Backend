<?php

namespace App\Console\Commands;

use App\Helpers\CallControlHelper;
use App\Models\ActiveAgentQueue;
use App\Models\AgentLedCampaignSetting;
use App\Models\CallLog;
use App\Models\CallServer;
use App\Models\Campaign;
use App\Models\CampaignContact;
use App\Models\CDRTable;
use App\Models\OutboundCallLogs;
use App\Models\Queue;
use App\Models\QueueLog;
use Carbon\Carbon;
use DB;
use Illuminate\Console\Command;

class RunAgentLedCampaignCalls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'run:agent_led_campaign_calls';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will make calls for agent led camapgins';

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
    /**
     * It makes outbound calls to the contacts in the campaign.
     */
    public function handle()
    {
        $today_day = Carbon::today()->setTimezone('Africa/Nairobi')->format('l');
        $now_time = Carbon::now()->setTimezone('Africa/Nairobi')->toTimeString();
        $agent_led_campaigns = DB::select('select `campaigns`.* from `campaigns` inner join `campaign_working_hours` on `campaigns`.`id` = `campaign_working_hours`.`campaign_id` where (`campaign_type_id` = "AGENTLED" and `status` = "STARTED" and `campaign_working_hours`.`date` = "' . $today_day . '") and TIME(`campaign_working_hours`.`starting_time`) < "' . $now_time . '" and TIME(`campaign_working_hours`.`end_time`) > "' . $now_time . '"');

        $call_server = CallServer::where("type", "OUTBOUND")->first();

        foreach ($agent_led_campaigns as $agent_led_campaign) {
            $camapign_setting = AgentLedCampaignSetting::where("campaign_id", $agent_led_campaign->id)->with('did_detail')->first();

            ///check if campaign_setting is available
            if ($camapign_setting) {
                ///check if agent is available in the queue
                $available_agent = ActiveAgentQueue::where([
                    "queue_id" => $camapign_setting->queue_id,
                    "status" => "ONLINE",
                    "sip_status" => "ONLINE",
                    "is_paused" => false
                ])->orderBy('last_call_hung_up_at', 'asc')->first();

                if ($available_agent) {
                    $campaign_contact = CampaignContact::where([
                        "campaign_id" => $agent_led_campaign->id,
                        "status" => "NOTCONTACTED"
                    ])->first();

                    //check if contact is available
                    if ($campaign_contact) {
                        $phone_number = $campaign_contact->phone_number;
                        if (preg_match('/^[0-9]{12}+$/', $campaign_contact->phone_number)) {
                            $phone = substr($campaign_contact->phone_number, 3);
                            $phone_number = '0' . $phone;
                        }
                        $queue = Queue::find($available_agent->queue_id);

                        $channel_out_id = \App\Helpers\CallControlHelper::call_endpoint($available_agent->sip_id, $phone_number, $call_server->server_name, $call_server->ip_address . ":" . $call_server->port);

                        $available_agent->update(["status" => "ONCALL"]);

                        OutboundCallLogs::create([
                            "sip_channel" => json_decode($channel_out_id[1])->id,
                            "sip_id" => $available_agent->sip_id,
                            "status" => "CALLING",
                            "phone_number" => $phone_number,
                            "source" => $call_server->server_name
                        ]);
                        CDRTable::create([
                            'call_id' => json_decode($channel_out_id[1])->id,
                            'phone_number' => $phone_number,
                            // "bridge_id" => $bridge_id,
                            "group_id" => $queue->group_id,
                            'call_date' => date("y-m-d"),
                            'call_time' => 0,
                            "hold_time" => 0,
                            "mute_time" => 0,
                            'desposition' => "CALLING",
                            'sip_id' => $available_agent->sip_id,
                            "user_id" => $available_agent->user_id,
                            "queue_id" => $available_agent->queue_id,
                            "company_id" => $queue->company_id,
                            "call_type" => "AGENT_CAMPAIGN",
                        ]);

                        CallLog::create([
                            "call_id" => json_decode($channel_out_id[1])->id,
                            "did" => $camapign_setting->did_detail->did,
                            "source" => $call_server->server_name,
                            "caller_id" => $campaign_contact->phone_number,
                            "call_status" => "CALLING",
                            "call_type" => "AGENT_CAMPAIGN",
                            "company_id" => $agent_led_campaign->company_id,
                            "campaign_contact_id" => $campaign_contact->id
                        ]);
                        QueueLog::create([
                            "call_id" => json_decode($channel_out_id[1])->id,
                            "caller_id" => $campaign_contact->phone_number,
                            "queue_id" => $camapign_setting->queue_id,
                            // "channel_in_id" => json_decode($channel_out_id[1])->id,
                            "call_type" => "AGENT_CAMPAIGN",
                            "status" => "CALLING",
                            "call_date" => date("y-m-d"),
                            "campaign_id" => $agent_led_campaign->id,
                            "campaign_contact_id" => $campaign_contact->id,
                            "group_id" => $queue->group_id,
                            "user_id" => $available_agent->user_id,
                            "sip_id" => $available_agent->sip_id,
                            "queue_id" => $available_agent->queue_id
                        ]);
                        $campaign_contact->update([
                            "trail" => $campaign_contact->trail + 1,
                            "status" => "CONTACTED"
                        ]);
                    }
                }
            }
        }
    }
}
