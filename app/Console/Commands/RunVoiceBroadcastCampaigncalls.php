<?php

namespace App\Console\Commands;

use App\Helpers\CallControlHelper;
use App\Models\CallLog;
use App\Models\CallServer;
use App\Models\CampaignContact;
use App\Models\VoiceBroadcastSetting;
use Carbon\Carbon;
use DB;
use Illuminate\Console\Command;

class RunVoiceBroadcastCampaigncalls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'run:voice_braodcast_campaign_calls';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will make calls for voice broadcast camapgins';

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
        $today_day = Carbon::today()->setTimezone('Africa/Nairobi')->format('l');
        $now_time = Carbon::now()->setTimezone('Africa/Nairobi')->toTimeString();
        $voice_broadcats_campaigns = DB::select('select `campaigns`.* from `campaigns` inner join `campaign_working_hours` on `campaigns`.`id` = `campaign_working_hours`.`campaign_id` where (`campaign_type_id` = "VOICEBROADCAST" and `status` = "STARTED" and `campaign_working_hours`.`date` = "' . $today_day . '") and TIME(`campaign_working_hours`.`starting_time`) < "' . $now_time . '" and TIME(`campaign_working_hours`.`end_time`) > "' . $now_time . '"');


        $call_server = CallServer::where("type", "OUTBOUND")->first();

        foreach ($voice_broadcats_campaigns as $voice_broadcats_campaign) {
            $camapign_setting = VoiceBroadcastSetting::where("campaign_id", $voice_broadcats_campaign->id)->with('did_detail')->first();
            // print_r($camapign_setting);
            ///check if campaign_setting is available
            if ($camapign_setting) {
                $campaign_contact = CampaignContact::where([
                    "campaign_id" => $voice_broadcats_campaign->id,
                    "status" => "NOTCONTACTED"
                ])->first();
                //check if contact is available
                if ($campaign_contact) {
                    $phone_number = $campaign_contact->phone_number;
                    if (preg_match('/^[0-9]{12}+$/', $campaign_contact->phone_number)) {
                        $phone = substr($campaign_contact->phone_number, 3);
                        $phone_number = '0' . $phone;
                    }
                    $call_sent_response =  CallControlHelper::call_phone_out($phone_number, $camapign_setting->did_detail->did, $call_server->server_name, $call_server->ip_address . ":" . $call_server->port);
                    // print_r($phone_number);
                    CallLog::create([
                        "call_id" => json_decode($call_sent_response[1])->id,
                        "did" => $camapign_setting->did_detail->did,
                        "source" => $call_server->server_name,
                        "caller_id" => $campaign_contact->phone_number,
                        "call_status" => "CALLING",
                        "call_type" => "VOICEBROADCAST",
                        "company_id" => $voice_broadcats_campaign->company_id,
                        "campaign_contact_id" => $campaign_contact->id
                    ]);
                    $trail = $campaign_contact->trail + 1;
                    $campaign_contact->update([
                        "trail" => $trail,
                        "status" => "CONTACTED"
                    ]);
                }
            }
        }
    }
}
