<?php

namespace App\Console\Commands;

use App\Events\AssignedConversationEvent;
use Illuminate\Console\Command;
use App\Models\AssignedConversation;
use App\Models\ChatQueue;
use App\Models\ChatQueueUser;
use App\Models\Conversation;
use App\Models\ConversationQueue;
use App\Models\User;

class AssignConversationToAgent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assign:conversation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign conversation to agents';

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
        $conversations = Conversation::where('status', 'ON-QUEUE')->orderBy('created_at', 'DESC')->get();

        if ($conversations) {
            foreach ($conversations as $conversation) {
                $conversationQueue = ConversationQueue::where([
                    'conversation_id' => $conversation->id,
                    'status' => 'UNASSIGNED'
                ])->first();

                if ($conversationQueue) {
                    $chatQueue = ChatQueue::find($conversationQueue->chat_queue_id);

                    $chatQueueUsers = ChatQueueUser::where('chat_queue_id', $conversationQueue->chat_queue_id)->get();

                    foreach ($chatQueueUsers as $chatQueueUser) {

                        $assignedConversations = AssignedConversation::where([
                            'agent_id' => $chatQueueUser->user_id,
                        ])
                            ->where(function ($query) {
                                $query->where('status', '=', 'ASSIGNED')
                                    ->orWhere('status', '=', 'ON-GOING');
                            })
                            ->count();

                        if ($assignedConversations < $chatQueue->max_open_conversation) {

                            $checkAssigned = AssignedConversation::where([
                                'conversation_id' => $conversation->id,
                            ])
                                ->where(function ($query) {
                                    $query->where('status', '=', 'ASSIGNED')
                                        ->orWhere('status', '=', 'ON-GOING');
                                })
                                ->first();

                            if (!$checkAssigned) {
                                $assignConv = AssignedConversation::create([
                                    'conversation_id' => $conversation->id,
                                    'channel_id' => $conversation->channel_id,
                                    'conv_queue_id' => $conversationQueue->id,
                                    'agent_id' => $chatQueueUser->user_id,
                                    'status' => 'ASSIGNED'
                                ]);

                                $conversationQueue->update([
                                    'status' => 'ASSIGNED',
                                    'assigned_at' => now()
                                ]);

                                $assign = Conversation::find($conversation->id);
                                $assign->status = 'ASSIGNED';
                                $assign->assigned_to = $chatQueueUser->user_id;
                                $assign->save();

                                $user = User::find($chatQueueUser->user_id);
                                $conversation = Conversation::find($conversation->id);

                                AssignedConversationEvent::dispatch($user, $conversation);
                            }
                        }
                    }
                }
            }
        } else {
            sleep(5);
        }
    }
}