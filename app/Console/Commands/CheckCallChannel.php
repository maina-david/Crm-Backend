<?php

namespace App\Console\Commands;

use App\Helpers\CallControlHelper;
use App\Models\CallLog;
use App\Models\CallServer;
use App\Models\CampaignContact;
use Illuminate\Console\Command;

class CheckCallChannel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'run:check_call_channel';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks outbound call channels if they are availbale, if not it will hungup the call';

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
        $call_log_data = CallLog::where("call_type", "!=", "INBOUND")->where("call_status", "CALLING")->get();
        foreach ($call_log_data as $key => $call_log) {
            $call_server = CallServer::where("server_name", $call_log->source)->first();
            $server = $call_server->ip_address . ":" . $call_server->port;
            try {
                $response = CallControlHelper::check_channel($call_log->call_id, $server);
            } catch (\Exception $ex) {
                if ($call_log->call_type == "VOICEBROADCAST") {
                    $campaign_contact = CampaignContact::find($call_log->campaign_contact_id);
                    if ($campaign_contact)
                        $campaign_contact->update(["status" => "CONTACTED", "desposition" => "NOTANSWERED"]);
                    $call_log->update(["call_status" => "ABANDONED"]);
                }
            }
        }
    }
}