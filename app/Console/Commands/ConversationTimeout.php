<?php

namespace App\Console\Commands;

use App\Models\AssignedConversation;
use App\Models\ChatQueue;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\ConversationQueue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ConversationTimeout extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:conversationTimeout';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check response time to a conversation';

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
        $conversations = Conversation::where('status', 'ASSIGNED')
            ->orWhere('status', 'ON-GOING')
            ->limit(1000)
            ->get();

        foreach ($conversations as $conversation) {
            $latestmessage = ConversationMessage::where('conversation_id', $conversation->id)
                ->orderBy('id', 'DESC')
                ->limit(1)
                ->first();

            if ($latestmessage->direction == 'INCOMING') {

                $conversationQueue = ConversationQueue::where('conversation_id', $conversation->id)
                    ->orderBy('id', 'DESC')
                    ->limit(1)
                    ->first();

                $chatQueue = ChatQueue::find($conversationQueue->chat_queue_id);

                if ($chatQueue) {
                    $interval = now()->diff($latestmessage->created_at);
                    $diffInSeconds = $interval->s;
                    if ($diffInSeconds > $chatQueue->timeout) {
                        //return conversation to queue
                        $updateConversation = Conversation::find($conversation->id);

                        $updateConversation->status = "ON-QUEUE";

                        $updateConversation->save();

                        $conversationQueue->update([
                            'status' => 'UNASSIGNED'
                        ]);

                        $assignedAgent = AssignedConversation::where('conversation_id', $conversation->id)
                            ->orderBy('id', 'DESC')
                            ->limit(1)
                            ->first();

                        $assignedAgent->update([
                            'status' => 'ABANDONED'
                        ]);
                    }
                }
            }
        }
    }
}