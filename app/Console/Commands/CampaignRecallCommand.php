<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Models\CampaignContact;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CampaignRecallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'run:camapign_recall';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will make contacts to be recalled if they fail first time';

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
        $campaigns = Campaign::where("status", "STARTED")->get();
        foreach ($campaigns as $campaign) {
            $company = Company::find($campaign->company_id);
            $current_time = Carbon::now();
            $calls_before = $current_time->addMinutes(- ($company->campaign_recall_interval));
            $contact = CampaignContact::WhereDate("updated_at", "<", $calls_before)
                ->where("trail", "<", "3")
                ->where(["campaign_id" => $campaign->id, "desposition" => "NOTANSWERED"])
                ->update(["status" => "NOTCONTACTED"]);
        }
    }
}
