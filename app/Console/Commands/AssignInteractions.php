<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ConversationQueue;
use App\Models\QAInteractionReview;
use App\Models\QATeamQueue;
use App\Models\QueueLog;
use App\Models\Interaction;

class AssignInteractions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assign:interactions {company}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assigns interactions to companys team';

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
        $companyId = $this->argument('company');

        $allInteractions = Interaction::doesntHave('review')
            ->where([
                'reviewed' => 0,
                'company_id' => $companyId
            ])
            ->get();

        if ($allInteractions->count() > 0) {
            foreach ($allInteractions as $key => $interaction) {

                $interactionType = null;
                $interactionQueue = null;

                if ($interaction->channel_id == 7) {
                    $interactionType = 'voice';
                    $queue = QueueLog::where(
                        'call_id',
                        $interaction->interaction_reference
                    )->first();

                    if ($queue) {
                        $interactionQueue = $queue->queue_id;
                    }
                } else {
                    $interactionType = 'chat';
                    $queue = ConversationQueue::where(
                        'conversation_id',
                        $interaction->interaction_reference
                    )->first();
                    if ($queue) {
                        $interactionQueue = $queue->chat_queue_id;
                    }
                }

                $QATeam_Queue = QATeamQueue::where([
                    "queue_id" => $interactionQueue,
                    "queue_type" => $interactionType
                ])->first();

                if ($QATeam_Queue) {
                    $interactionReview = QAInteractionReview::create([
                        'company_id' => $interaction->company_id,
                        'interaction_id' => $interaction->id,
                        'q_a_team_id' => $QATeam_Queue->team_id,
                        'status' => 'NOT-REVIEWED'
                    ]);
                }
            }
        }
    }
}