<?php

namespace App\Console\Commands;

use App\Models\CallServer;
use App\Models\Campaign;
use App\Models\CampaignContact;
use App\Services\PhoneFormatterService;
use Carbon\Carbon;
use DB;
use Illuminate\Console\Command;

class RunSMSCampaignCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'run:sms_campaigns';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Will send sms from campaign using the setting given';

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
        $sms_campaigns = DB::select('select `campaigns`.* from `campaigns` inner join `campaign_working_hours` on `campaigns`.`id` = `campaign_working_hours`.`campaign_id` where (`campaign_type_id` = "SMSCAMPAIGN" and `status` = "STARTED" and `campaign_working_hours`.`date` = "' . $today_day . '") and TIME(`campaign_working_hours`.`starting_time`) < "' . $now_time . '" and TIME(`campaign_working_hours`.`end_time`) > "' . $now_time . '"');
        foreach ($sms_campaigns as $sms_campaign) {
            $campaign = Campaign::find($sms_campaign->id);
            $campaign_contacts = CampaignContact::where([
                "campaign_id" => $sms_campaign->id,
                "status" => "NOTCONTACTED"
            ])->limit(20)->get();
            foreach ($campaign_contacts as $campaign_contact) {
                $phone = PhoneFormatterService::format_phone($campaign_contact->phone_number);
                $response = sendsms($campaign->sms_setting->sms_account_id, $phone, $campaign->sms_setting->sms_text);
                print_r($response);
            }
        }
    }
}
