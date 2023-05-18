<?php

namespace App\Handler;

use Illuminate\Support\Facades\Log;
use App\Models\ChatBotAccountPivot;
use App\Models\ChatBotFlow;
use App\Models\ChatBotLink;
use App\Models\ChatBotLog;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\ConversationQueue;
use App\Models\TwitterAccount;
use Abraham\TwitterOAuth\TwitterOAuth;
use App\Events\NewConversationMessageEvent;
use App\Models\AssignedConversation;
use App\Models\ChatBotFile;
use App\Models\User;
use \Spatie\WebhookClient\ProcessWebhookJob;

class TwitterWebhookHandler extends ProcessWebhookJob
{
    public function handle()
    {
        $message = $this->webhookCall['payload'];

        if (strpos(json_encode($message), 'direct_message_events') > 0) {

            $AccountID = $message['for_user_id'];
            $customerID = $message['direct_message_events'][0]['message_create']['sender_id'];
            $clientName = $message['users'][$customerID]['name'];
            $clientMessage = $message['direct_message_events'][0]['message_create']['message_data']['text'];
            if ($customerID == $AccountID) {
                return response()->json('success', 200);
            }
            if ($AccountID) {
                $account = TwitterAccount::where('account_id', $AccountID)->first();

                if ($account) {

                    $chatBot = ChatBotAccountPivot::where([
                        'channel_id' => 4,
                        'account_id' => $account->id
                    ])->first();

                    if ($chatBot) {

                        /* Getting the first chatbot flow that has the application type of start. */
                        $chatbot_flow = ChatBotFlow::where([
                            'chatbot_id' => $chatBot->chatbot_id,
                            'application_type' => 'Start'
                        ])
                            ->first();

                        //check if there are conversations with the same number

                        $currentConversation = Conversation::where([
                            'customer_id' => $customerID,
                            'company_id' => $account->company_id
                        ])->orderBy('id', 'DESC')->limit(1)->first();


                        if ($currentConversation) {
                            if ($currentConversation->status == 'CLOSED') {
                                //create new conversation
                                $conversation = Conversation::create([
                                    'company_id' => $account->company_id,
                                    'phone_number_id' => $AccountID,
                                    'customer_id' => $customerID,
                                    'channel_id' => 2,
                                    'customer_name' => $clientName,
                                    'status' => "ON-BOT",
                                ]);

                                $conversationMessage = ConversationMessage::create([
                                    'conversation_id' => $conversation->id,
                                    'message' => $clientMessage,
                                    'direction' => 'INCOMING'
                                ]);

                                $continue_loop = true;
                                while ($continue_loop === true) {

                                    $nextStep = ChatBotFlow::where('parent_id', $chatbot_flow->id)->first();
                                    if ($nextStep->application_type == "SendText") {
                                        $chatbotLog = ChatBotLog::create([
                                            'conversation_id' => $conversation->id,
                                            'chat_flow_id' => $chatbot_flow->id,
                                            'current_flow_id' => $nextStep->id
                                        ]);
                                        $this->sendMessage($customerID, $account->account_id, $nextStep->application_data);
                                    } elseif ($nextStep->application_type == "SendTextWait") {
                                        $chatbotLog = ChatBotLog::create([
                                            'conversation_id' => $conversation->id,
                                            'chat_flow_id' => $chatbot_flow->id,
                                            'current_flow_id' => $nextStep->id
                                        ]);
                                        $this->sendMessage($customerID, $account->account_id, $nextStep->application_data);
                                        break;
                                    } elseif ($nextStep->application_type == "Attachment") {
                                        $chatbotLog = ChatBotLog::create([
                                            'conversation_id' => $conversation->id,
                                            'chat_flow_id' => $chatbot_flow->id,
                                            'current_flow_id' => $nextStep->id
                                        ]);

                                        $fileAttachment = ChatBotFile::find($nextStep->application_data);

                                        if ($fileAttachment) {
                                            $this->sendMessage($customerID, $account->account_id, $fileAttachment->file_url);
                                        } else {
                                            return response()->json('success', 200);
                                        }
                                    } elseif ($nextStep->application_type == "Queue") {
                                        $chatbotLog = ChatBotLog::create([
                                            'conversation_id' => $conversation->id,
                                            'chat_flow_id' => $chatbot_flow->id,
                                            'current_flow_id' => $nextStep->id
                                        ]);

                                        //change status to on queue
                                        $updatedConversation = Conversation::find($conversation->id);
                                        $updatedConversation->status = 'ON-QUEUE';
                                        $updatedConversation->save();

                                        $conversationQueue = ConversationQueue::create([
                                            'conversation_id' => $conversation->id,
                                            'chat_queue_id' => $nextStep->application_data
                                        ]);

                                        break;
                                    } elseif ($nextStep->application_type == "Stop") {
                                        $chatbotLog = ChatBotLog::create([
                                            'conversation_id' => $conversation->id,
                                            'chat_flow_id' => $chatbot_flow->id,
                                            'current_flow_id' => $nextStep->id
                                        ]);

                                        //change status to closed
                                        $updatedConversation = Conversation::find($conversation->id);
                                        $updatedConversation->status = 'CLOSED';
                                        $updatedConversation->save();
                                        break;
                                    }
                                    $chatbot_flow = $nextStep;
                                }
                            } else if ($currentConversation->status == 'ON-BOT') {
                                if ($clientMessage == 'EXIT' || $clientMessage == 'Exit' || $clientMessage == 'exit') {
                                    $currentConversation->status = 'CLOSED';
                                    $currentConversation->save();
                                    $this->sendMessage($customerID, $account->account_id, 'Goodbye.');
                                } else {
                                    $current_step = ChatBotLog::where('conversation_id', $currentConversation->id)
                                        ->orderBy('id', 'DESC')->limit(1)->first();

                                    //get whether the text is a valid selection
                                    $input =  $clientMessage;

                                    //select flow from flow table where selection is $selection 

                                    $selection = ChatBotLink::where([
                                        'selection' => $input,
                                        'chatbot_flow_id' => $current_step->current_flow_id
                                    ])->limit(1)->first();

                                    if (!$selection) {
                                        //If selection if it returns null selection is invalid. 
                                        $this->sendMessage($customerID, $account->account_id, 'Invalid selection. Try again!');
                                    } else {
                                        $current_step->update(['selection' => $input]);

                                        // if it returns true get first step 
                                        $next = ChatBotLink::where([
                                            'chatbot_flow_id' => $current_step->current_flow_id,
                                            'selection' => $input
                                        ])->first();

                                        $nextStep = ChatBotFlow::find($next->next_flow_id);

                                        if ($nextStep->application_type == "SendText") {
                                            $chatbotLog = ChatBotLog::create([
                                                'conversation_id' => $currentConversation->id,
                                                'chat_flow_id' => $chatbot_flow->id,
                                                'current_flow_id' => $nextStep->id
                                            ]);
                                            $this->sendMessage($customerID, $account->account_id, $nextStep->application_data);

                                            $continue_loop = true;
                                            while ($continue_loop === true) {

                                                $nextStep = ChatBotFlow::where('parent_id', $nextStep->id)->first();
                                                if ($nextStep->application_type == "SendText") {
                                                    $chatbotLog = ChatBotLog::create([
                                                        'conversation_id' => $currentConversation->id,
                                                        'chat_flow_id' => $chatbot_flow->id,
                                                        'current_flow_id' => $nextStep->id
                                                    ]);
                                                    $this->sendMessage($customerID, $account->account_id, $nextStep->application_data);
                                                } elseif ($nextStep->application_type == "SendTextWait") {
                                                    $chatbotLog = ChatBotLog::create([
                                                        'conversation_id' => $currentConversation->id,
                                                        'chat_flow_id' => $chatbot_flow->id,
                                                        'current_flow_id' => $nextStep->id
                                                    ]);
                                                    $this->sendMessage($customerID, $account->account_id, $nextStep->application_data);
                                                    break;
                                                } elseif ($nextStep->application_type == "Attachment") {
                                                    $chatbotLog = ChatBotLog::create([
                                                        'conversation_id' => $currentConversation->id,
                                                        'chat_flow_id' => $chatbot_flow->id,
                                                        'current_flow_id' => $nextStep->id
                                                    ]);
                                                    $fileAttachment = ChatBotFile::find($nextStep->application_data);
                                                    if ($fileAttachment) {
                                                        $this->sendMessage($customerID, $account->account_id, $fileAttachment->file_url);
                                                    } else {
                                                        return response()->json('success', 200);
                                                    }
                                                } elseif ($nextStep->application_type == "Queue") {
                                                    $chatbotLog = ChatBotLog::create([
                                                        'conversation_id' => $currentConversation->id,
                                                        'chat_flow_id' => $chatbot_flow->id,
                                                        'current_flow_id' => $nextStep->id
                                                    ]);

                                                    //change status to on queue
                                                    $updatedConversation = Conversation::find($currentConversation->id);
                                                    $updatedConversation->status = 'ON-QUEUE';
                                                    $updatedConversation->save();

                                                    $conversationQueue = ConversationQueue::create([
                                                        'conversation_id' => $currentConversation->id,
                                                        'chat_queue_id' => $nextStep->application_data
                                                    ]);

                                                    break;
                                                } elseif ($nextStep->application_type == "Stop") {
                                                    $chatbotLog = ChatBotLog::create([
                                                        'conversation_id' => $currentConversation->id,
                                                        'chat_flow_id' => $chatbot_flow->id,
                                                        'current_flow_id' => $nextStep->id
                                                    ]);

                                                    //change status to closed
                                                    $updatedConversation = Conversation::find($currentConversation->id);
                                                    $updatedConversation->status = 'CLOSED';
                                                    $updatedConversation->save();
                                                    break;
                                                }
                                            }
                                        } elseif ($nextStep->application_type == "SendTextWait") {
                                            $chatbotLog = ChatBotLog::create([
                                                'conversation_id' => $currentConversation->id,
                                                'chat_flow_id' => $chatbot_flow->id,
                                                'current_flow_id' => $nextStep->id
                                            ]);
                                            $this->sendMessage($customerID, $account->account_id, $nextStep->application_data);
                                        } elseif ($nextStep->application_type == "Attachment") {
                                            $chatbotLog = ChatBotLog::create([
                                                'conversation_id' => $currentConversation->id,
                                                'chat_flow_id' => $chatbot_flow->id,
                                                'current_flow_id' => $nextStep->id
                                            ]);
                                            $fileAttachment = ChatBotFile::find($nextStep->application_data);
                                            if ($fileAttachment) {
                                                $this->sendMessage($customerID, $account->account_id, $fileAttachment->file_url);
                                            } else {
                                                return response()->json('success', 200);
                                            }
                                            $continue_loop = true;
                                            while ($continue_loop === true) {

                                                $nextStep = ChatBotFlow::where('parent_id', $nextStep->id)->first();
                                                if ($nextStep->application_type == "SendText") {
                                                    $chatbotLog = ChatBotLog::create([
                                                        'conversation_id' => $currentConversation->id,
                                                        'chat_flow_id' => $chatbot_flow->id,
                                                        'current_flow_id' => $nextStep->id
                                                    ]);
                                                    $this->sendMessage($customerID, $account->account_id, $nextStep->application_data);
                                                } elseif ($nextStep->application_type == "SendTextWait") {
                                                    $chatbotLog = ChatBotLog::create([
                                                        'conversation_id' => $currentConversation->id,
                                                        'chat_flow_id' => $chatbot_flow->id,
                                                        'current_flow_id' => $nextStep->id
                                                    ]);
                                                    $this->sendMessage($customerID, $account->account_id, $nextStep->application_data);
                                                    break;
                                                } elseif ($nextStep->application_type == "Attachment") {
                                                    $chatbotLog = ChatBotLog::create([
                                                        'conversation_id' => $currentConversation->id,
                                                        'chat_flow_id' => $chatbot_flow->id,
                                                        'current_flow_id' => $nextStep->id
                                                    ]);
                                                    $fileAttachment = ChatBotFile::find($nextStep->application_data);
                                                    if ($fileAttachment) {
                                                        $this->sendMessage($customerID, $account->account_id, $fileAttachment->file_url);
                                                    } else {
                                                        return response()->json('success', 200);
                                                    }
                                                } elseif ($nextStep->application_type == "Queue") {
                                                    $chatbotLog = ChatBotLog::create([
                                                        'conversation_id' => $currentConversation->id,
                                                        'chat_flow_id' => $chatbot_flow->id,
                                                        'current_flow_id' => $nextStep->id
                                                    ]);

                                                    //change status to on queue
                                                    $updatedConversation = Conversation::find($currentConversation->id);
                                                    $updatedConversation->status = 'ON-QUEUE';
                                                    $updatedConversation->save();

                                                    $conversationQueue = ConversationQueue::create([
                                                        'conversation_id' => $currentConversation->id,
                                                        'chat_queue_id' => $nextStep->application_data
                                                    ]);

                                                    break;
                                                } elseif ($nextStep->application_type == "Stop") {
                                                    $chatbotLog = ChatBotLog::create([
                                                        'conversation_id' => $currentConversation->id,
                                                        'chat_flow_id' => $chatbot_flow->id,
                                                        'current_flow_id' => $nextStep->id
                                                    ]);

                                                    //change status to closed
                                                    $updatedConversation = Conversation::find($currentConversation->id);
                                                    $updatedConversation->status = 'CLOSED';
                                                    $updatedConversation->save();

                                                    break;
                                                }
                                            }
                                        } elseif ($nextStep->application_type == "Queue") {
                                            $chatbotLog = ChatBotLog::create([
                                                'conversation_id' => $currentConversation->id,
                                                'chat_flow_id' => $chatbot_flow->id,
                                                'current_flow_id' => $nextStep->id
                                            ]);

                                            //change status to on queue
                                            $updatedConversation = Conversation::find($currentConversation->id);
                                            $updatedConversation->status = 'ON-QUEUE';
                                            $updatedConversation->save();

                                            $conversationQueue = ConversationQueue::create([
                                                'conversation_id' => $currentConversation->id,
                                                'chat_queue_id' => $nextStep->application_data
                                            ]);
                                        } elseif ($nextStep->application_type == "Stop") {
                                            $chatbotLog = ChatBotLog::create([
                                                'conversation_id' => $currentConversation->id,
                                                'chat_flow_id' => $chatbot_flow->id,
                                                'current_flow_id' => $nextStep->id
                                            ]);

                                            //change status to closed
                                            $updatedConversation = Conversation::find($currentConversation->id);
                                            $updatedConversation->status = 'CLOSED';
                                            $updatedConversation->save();
                                        }
                                    }
                                }
                            } else if ($currentConversation->status == 'ON-QUEUE') {
                                ConversationMessage::create([
                                    'conversation_id' => $currentConversation->id,
                                    'message' => $clientMessage,
                                    'direction' => 'INCOMING'
                                ]);
                                $this->sendMessage($customerID, $account->account_id, 'You are currently on queue. Please wait while we connect you to an available agent.');
                            } else if ($currentConversation->status == 'ASSIGNED' || $currentConversation->status == 'ON-GOING') {
                                $conversationMessage = ConversationMessage::create([
                                    'conversation_id' => $currentConversation->id,
                                    'message_level' => 'ON-GOING',
                                    'message' => $clientMessage,
                                    'direction' => 'INCOMING'
                                ]);


                                $assignedConversation = AssignedConversation::where([
                                    'conversation_id' => $currentConversation->id,
                                ])
                                    ->where(function ($query) {
                                        $query->where('status', '=', 'ASSIGNED')
                                            ->orWhere('status', '=', 'ON-GOING');
                                    })
                                    ->first();

                                if ($assignedConversation) {
                                    $user = User::find($assignedConversation->agent_id);

                                    NewConversationMessageEvent::dispatch($user, $conversationMessage);
                                }
                                return response()->json('success', 200);
                            }
                        } else { //end conversation check. create new

                            //create new conversation
                            $conversation = Conversation::create([
                                'company_id' => $account->company_id,
                                'phone_number_id' => $AccountID,
                                'customer_id' => $customerID,
                                'channel_id' => 2,
                                'customer_name' => $clientName,
                                'status' => "ON-BOT",
                            ]);

                            $conversationMessage = ConversationMessage::create([
                                'conversation_id' => $conversation->id,
                                'message' => $clientMessage,
                                'direction' => 'INCOMING'
                            ]);

                            $continue_loop = true;
                            $nextStep = $chatbot_flow;
                            while ($continue_loop === true) {
                                $nextStep = ChatBotFlow::where('parent_id', $nextStep->id)->first();
                                if ($nextStep->application_type == "SendText") {
                                    $chatbotLog = ChatBotLog::create([
                                        'conversation_id' => $conversation->id,
                                        'chat_flow_id' => $chatbot_flow->id,
                                        'current_flow_id' => $nextStep->id
                                    ]);
                                    $this->sendMessage($customerID, $account->account_id,  $nextStep->application_data);
                                } elseif ($nextStep->application_type == "SendTextWait") {
                                    $chatbotLog = ChatBotLog::create([
                                        'conversation_id' => $conversation->id,
                                        'chat_flow_id' => $chatbot_flow->id,
                                        'current_flow_id' => $nextStep->id
                                    ]);
                                    $this->sendMessage($customerID, $account->account_id, $nextStep->application_data);
                                    break;
                                } elseif ($nextStep->application_type == "Attachment") {
                                    $chatbotLog = ChatBotLog::create([
                                        'conversation_id' => $conversation->id,
                                        'chat_flow_id' => $chatbot_flow->id,
                                        'current_flow_id' => $nextStep->id
                                    ]);
                                    $fileAttachment = ChatBotFile::find($nextStep->application_data);
                                    if ($fileAttachment) {
                                        $this->sendMessage($customerID, $account->account_id, $fileAttachment->file_url);
                                    } else {
                                        return response()->json('success', 200);
                                    }
                                } elseif ($nextStep->application_type == "Queue") {
                                    $chatbotLog = ChatBotLog::create([
                                        'conversation_id' => $conversation->id,
                                        'chat_flow_id' => $chatbot_flow->id,
                                        'current_flow_id' => $nextStep->id
                                    ]);

                                    //change status to on queue
                                    $updatedConversation = Conversation::find($conversation->id);
                                    $updatedConversation->status = 'ON-QUEUE';
                                    $updatedConversation->save();

                                    $conversationQueue = ConversationQueue::create([
                                        'conversation_id' => $conversation->id,
                                        'chat_queue_id' => $nextStep->application_data
                                    ]);

                                    break;
                                } elseif ($nextStep->application_type == "Stop") {
                                    $chatbotLog = ChatBotLog::create([
                                        'conversation_id' => $conversation->id,
                                        'chat_flow_id' => $chatbot_flow->id,
                                        'current_flow_id' => $nextStep->id
                                    ]);

                                    //change status to closed
                                    $updatedConversation = Conversation::find($conversation->id);
                                    $updatedConversation->status = 'CLOSED';
                                    $updatedConversation->save();
                                    break;
                                }
                            }
                        }
                    } else {
                        Log::critical("Twitter account not linked to flow!");
                    }
                } else {
                    Log::critical("Twitter account not found!");
                }
            }else {
                Log::critical("Twitter account not received!");
            }
        }
    }


    /**
     * Send Twitter message.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function sendMessage($clientID, $account_id, $appData)
    {
        $account = TwitterAccount::where('account_id', $account_id)->first();
        $data = [
            'event' => [
                'type' => 'message_create',
                'message_create' => [
                    'target' => [
                        'recipient_id' => $clientID
                    ],
                    'message_data' => [
                        'text' => $appData
                    ]
                ]
            ]
        ];
        $connection = new TwitterOAuth($account->consumer_key, $account->consumer_secret, $account->access_token, $account->access_token_secret);
        $content = $connection->post('direct_messages/events/new', $data, true);

        return $content;
    }
}