<?php

namespace App\Console\Commands;

use App\Models\QAInteractionReview;
use App\Models\QASetting;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AddInteractionsToReview extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'review:interactions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add interactions to quality assurance review';

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
        $QASettings = QASetting::get();

        if ($QASettings->count() > 0) {
            foreach ($QASettings as $QASetting) {
                $lastRunInteraction = QAInteractionReview::where('company_id', $QASetting->company_id)
                    ->orderBy('id', 'DESC')
                    ->first();

                $isDue = FALSE;

                if ($lastRunInteraction) {
                    $frequency = $QASetting->frequency;

                    $date = Carbon::createFromDate($lastRunInteraction->created_at);

                    $totalLastRunInteraction = QAInteractionReview::where('company_id', $QASetting->company_id)
                        ->whereDate('created_at', '>=', $date->subDays($frequency))
                        ->whereDate('created_at', '<=', $date)
                        ->count();

                    if ($QASetting->max_interactions > $totalLastRunInteraction) {
                        $isDue = TRUE;
                    }
                } else {
                    $isDue = TRUE;
                }

                if ($isDue) {
                    $this->call('assign:interactions', [
                        'company' => $QASetting->company_id
                    ]);
                }
            }
        }
    }
}