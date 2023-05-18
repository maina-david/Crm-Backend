<?php

namespace App\Handler;

use App\Events\NewConversationMessageEvent;
use App\Helpers\ChatMediaHelper;
use App\Models\AssignedConversation;
use Illuminate\Support\Facades\Log;
use App\Models\ChatBotAccountPivot;
use App\Models\ChatBotFile;
use App\Models\ChatBotFlow;
use App\Models\ChatBotLink;
use App\Models\ChatBotLog;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\ConversationQueue;
use App\Models\MetaAccessToken;
use App\Models\User;
use App\Models\WhatsappAccount;
use Illuminate\Support\Facades\Http;
use \Spatie\WebhookClient\ProcessWebhookJob;

class WhatsAppWebhookHandler extends ProcessWebhookJob
{
    /**
     * It checks if the message is a text message, if it is, it checks if the message is an exit message,
     * if it is, it closes the conversation, if it isn't, it checks if the message is a valid selection, if
     * it is, it gets the next step, if it isn't, it sends an invalid selection message
     * 
     * @return The return value is a JSON object with the following fields:
     */
    public function handle()
    {
        $message = $this->webhookCall['payload'];

        if (array_key_exists('messages', $message['entry'][0]['changes'][0]['value'])) {
            $companyphoneId = $message['entry'][0]['changes'][0]['value']['metadata']['phone_number_id'];
            $clientPhone = $message['entry'][0]['changes'][0]['value']['contacts'][0]['wa_id'];
            $clientName = $message['entry'][0]['changes'][0]['value']['contacts'][0]['profile']['name'];

            $messageType = $message['entry'][0]['changes'][0]['value']['messages'][0]['type'];

            $attachment = "";
            $clientMessage = "";
            $attachmentType = "";
            if ($companyphoneId) {
                $account = WhatsappAccount::where('phone_number_id', $companyphoneId)->first();

                if ($account) {

                    $chatBot = ChatBotAccountPivot::where([
                        'channel_id' => 1,
                        'account_id' => $account->id
                    ])->first();

                    if ($chatBot) {

                        if ($messageType == 'text') {
                            $clientMessage = $message['entry'][0]['changes'][0]['value']['messages'][0]['text']['body'];
                        } elseif ($messageType == 'contacts') {
                            $clientMessage = 'First Name: ' . $message['entry'][0]['changes'][0]['value']['messages'][0]['contacts']['name']['first_name'];
                            $clientMessage .= 'Formatted Name: ' . $message['entry'][0]['changes'][0]['value']['messages'][0]['contacts']['name']['formatted_name'];
                            $clientMessage .= 'Type : ' . $message['entry'][0]['changes'][0]['value']['messages'][0]['contacts']['phones']['type'];
                            $clientMessage .= 'Phone : ' . $message['entry'][0]['changes'][0]['value']['messages'][0]['contacts']['phones']['phone'];
                        } elseif ($messageType == 'location') {
                            $clientMessage = $message['entry'][0]['changes'][0]['value']['messages'][0]['location'];
                        } elseif ($messageType == 'audio') {
                            $mediaID = $message['entry'][0]['changes'][0]['value']['messages'][0]['audio']['id'];
                            $extension = $message['entry'][0]['changes'][0]['value']['messages'][0]['audio']['mime_type'];
                            $attachment = ChatMediaHelper::whatsApp($account->company_id, $mediaID, $messageType, $extension);
                            $attachmentType = "audio";
                            if (array_key_exists("caption", $message['entry'][0]['changes'][0]['value']['messages'][0]['audio'])) {
                                $clientMessage = $message['entry'][0]['changes'][0]['value']['messages'][0]['audio']['caption'];
                            }
                        } elseif ($messageType == 'image') {
                            $mediaID = $message['entry'][0]['changes'][0]['value']['messages'][0]['image']['id'];
                            $extension = $message['entry'][0]['changes'][0]['value']['messages'][0]['image']['mime_type'];
                            $attachment = ChatMediaHelper::whatsApp($account->company_id, $mediaID, $messageType, $extension);
                            $attachmentType = "image";
                            if (array_key_exists("caption", $message['entry'][0]['changes'][0]['value']['messages'][0]['image'])) {
                                $clientMessage = $message['entry'][0]['changes'][0]['value']['messages'][0]['image']['caption'];
                            }
                        } elseif ($messageType == 'video') {
                            $mediaID = $message['entry'][0]['changes'][0]['value']['messages'][0]['video']['id'];
                            $extension = $message['entry'][0]['changes'][0]['value']['messages'][0]['video']['mime_type'];
                            $attachment = ChatMediaHelper::whatsApp($account->company_id, $mediaID, $messageType, $extension);
                            $attachmentType = "video";
                            if (array_key_exists("caption", $message['entry'][0]['changes'][0]['value']['messages'][0]['video'])) {
                                $clientMessage = $message['entry'][0]['changes'][0]['value']['messages'][0]['video']['caption'];
                            }
                        } elseif ($messageType == 'document') {
                            $mediaID = $message['entry'][0]['changes'][0]['value']['messages'][0]['document']['id'];
                            $extension = $message['entry'][0]['changes'][0]['value']['messages'][0]['document']['filename'];
                            $attachment = ChatMediaHelper::whatsApp($account->company_id, $mediaID, $messageType, $extension);
                            $attachmentType = "document";
                            if (array_key_exists("caption", $message['entry'][0]['changes'][0]['value']['messages'][0]['document'])) {
                                $clientMessage = $message['entry'][0]['changes'][0]['value']['messages'][0]['document']['caption'];
                            }
                        } else {
                            return response()->json('success', 200);
                        }

                        /* Getting the first chatbot flow that has the application type of start. */
                        $chatbot_flow = ChatBotFlow::where([
                            'chatbot_id' => $chatBot->chatbot_id,
                            'application_type' => 'Start'
                        ])
                            ->first();

                        //check if there are conversations with the same number

                        $currentConversation = Conversation::where([
                            'customer_id' => $clientPhone,
                            'company_id' => $account->company_id
                        ])->orderBy('id', 'DESC')->limit(1)->first();


                        if ($currentConversation) {
                            if ($currentConversation->status == 'CLOSED') {
                                //create new conversation
                                $conversation = Conversation::create([
                                    'company_id' => $account->company_id,
                                    'phone_number_id' => $companyphoneId,
                                    'customer_id' => $clientPhone,
                                    'channel_id' => 1,
                                    'customer_name' => $clientName,
                                    'status' => "ON-BOT",
                                ]);

                                $conversationMessage = ConversationMessage::create([
                                    'conversation_id' => $conversation->id,
                                    'message' => $clientMessage,
                                    'message_type' => $messageType,
                                    'attachment' => $attachment,
                                    'attachment_type' => $attachmentType,
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
                                        $this->sendMessage($clientPhone, $account->phone_number_id, $account->company_id, $nextStep->application_data, 'text', $fileUrl = NULL);
                                    } elseif ($nextStep->application_type == "SendTextWait") {
                                        $chatbotLog = ChatBotLog::create([
                                            'conversation_id' => $conversation->id,
                                            'chat_flow_id' => $chatbot_flow->id,
                                            'current_flow_id' => $nextStep->id
                                        ]);
                                        $this->sendMessage($clientPhone, $account->phone_number_id, $account->company_id, $nextStep->application_data, 'text', $fileUrl = NULL);
                                        break;
                                    } elseif ($nextStep->application_type == "Attachment") {
                                        $chatbotLog = ChatBotLog::create([
                                            'conversation_id' => $conversation->id,
                                            'chat_flow_id' => $chatbot_flow->id,
                                            'current_flow_id' => $nextStep->id
                                        ]);

                                        $fileAttachment = ChatBotFile::find($nextStep->application_data);

                                        if ($fileAttachment) {
                                            $type = $fileAttachment->file_type;

                                            $this->sendMessage($clientPhone, $account->phone_number_id, $account->company_id, $message = NULL, $type, $fileAttachment->name, $fileAttachment->file_url);
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
                                    $this->sendMessage($clientPhone, $account->phone_number_id, $account->company_id, 'Goodbye.', 'text', $fileUrl = NULL);
                                } else {
                                    $current_step = ChatBotLog::where('conversation_id', $currentConversation->id)
                                        ->orderBy('id', 'DESC')->limit(1)->first();

                                    //get whether the text is a valid selection
                                    $input = $clientMessage;

                                    //select flow from flow table where selection is $selection 

                                    $selection = ChatBotLink::where([
                                        'selection' => $input,
                                        'chatbot_flow_id' => $current_step->current_flow_id
                                    ])->limit(1)->first();

                                    if (!$selection) {
                                        //If selection if it returns null selection is invalid. 
                                        $this->sendMessage($clientPhone, $account->phone_number_id, $account->company_id, 'Invalid selection. Try again!', 'text', $fileUrl = NULL);
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
                                            $this->sendMessage($clientPhone, $account->phone_number_id, $account->company_id, $nextStep->application_data, 'text', $fileUrl = NULL);

                                            $continue_loop = true;
                                            while ($continue_loop === true) {

                                                $nextStep = ChatBotFlow::where('parent_id', $nextStep->id)->first();
                                                if ($nextStep->application_type == "SendText") {
                                                    $chatbotLog = ChatBotLog::create([
                                                        'conversation_id' => $currentConversation->id,
                                                        'chat_flow_id' => $chatbot_flow->id,
                                                        'current_flow_id' => $nextStep->id
                                                    ]);
                                                    $this->sendMessage($clientPhone, $account->phone_number_id, $account->company_id, $nextStep->application_data, 'text', $fileUrl = NULL);
                                                } elseif ($nextStep->application_type == "SendTextWait") {
                                                    $chatbotLog = ChatBotLog::create([
                                                        'conversation_id' => $currentConversation->id,
                                                        'chat_flow_id' => $chatbot_flow->id,
                                                        'current_flow_id' => $nextStep->id
                                                    ]);
                                                    $this->sendMessage($clientPhone, $account->phone_number_id, $account->company_id, $nextStep->application_data, 'text', $fileUrl = NULL);
                                                    break;
                                                } elseif ($nextStep->application_type == "Attachment") {
                                                    $chatbotLog = ChatBotLog::create([
                                                        'conversation_id' => $currentConversation->id,
                                                        'chat_flow_id' => $chatbot_flow->id,
                                                        'current_flow_id' => $nextStep->id
                                                    ]);
                                                    $fileAttachment = ChatBotFile::find($nextStep->application_data);
                                                    if ($fileAttachment) {
                                                        $type = $fileAttachment->file_type;

                                                        $this->sendMessage($clientPhone, $account->phone_number_id, $account->company_id, $message = NULL, $type, $fileAttachment->name, $fileAttachment->file_url);
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
                                            $this->sendMessage($clientPhone, $account->phone_number_id, $account->company_id, $nextStep->application_data, 'text', $fileUrl = NULL);
                                        } elseif ($nextStep->application_type == "Attachment") {
                                            $chatbotLog = ChatBotLog::create([
                                                'conversation_id' => $currentConversation->id,
                                                'chat_flow_id' => $chatbot_flow->id,
                                                'current_flow_id' => $nextStep->id
                                            ]);
                                            $fileAttachment = ChatBotFile::find($nextStep->application_data);
                                            if ($fileAttachment) {
                                                $type = $fileAttachment->file_type;

                                                $this->sendMessage($clientPhone, $account->phone_number_id, $account->company_id, $message = NULL, $type, $fileAttachment->name, $fileAttachment->file_url);
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
                                                    $this->sendMessage($clientPhone, $account->phone_number_id, $account->company_id, $nextStep->application_data, 'text', $fileUrl = NULL);
                                                } elseif ($nextStep->application_type == "SendTextWait") {
                                                    $chatbotLog = ChatBotLog::create([
                                                        'conversation_id' => $currentConversation->id,
                                                        'chat_flow_id' => $chatbot_flow->id,
                                                        'current_flow_id' => $nextStep->id
                                                    ]);
                                                    $this->sendMessage($clientPhone, $account->phone_number_id, $account->company_id, $nextStep->application_data, 'text', $fileUrl = NULL);
                                                    break;
                                                } elseif ($nextStep->application_type == "Attachment") {
                                                    $chatbotLog = ChatBotLog::create([
                                                        'conversation_id' => $currentConversation->id,
                                                        'chat_flow_id' => $chatbot_flow->id,
                                                        'current_flow_id' => $nextStep->id
                                                    ]);
                                                    $fileAttachment = ChatBotFile::find($nextStep->application_data);
                                                    if ($fileAttachment) {
                                                        $type = $fileAttachment->file_type;

                                                        $this->sendMessage($clientPhone, $account->phone_number_id, $account->company_id, $message = NULL, $type, $fileAttachment->name, $fileAttachment->file_url);
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
                                    'message_type' => $messageType,
                                    'attachment' => $attachment,
                                    'attachment_type' => $attachmentType,
                                    'direction' => 'INCOMING'
                                ]);
                                $this->sendMessage($clientPhone, $account->phone_number_id, $account->company_id, 'You are currently on queue. Please wait while we connect you to an available agent.', 'text', $fileUrl = NULL);
                            } else if ($currentConversation->status == 'ASSIGNED' || $currentConversation->status == 'ON-GOING') {
                                $conversationMessage = ConversationMessage::create([
                                    'conversation_id' => $currentConversation->id,
                                    'message_level' => 'ON-GOING',
                                    'message' => $clientMessage,
                                    'message_type' => $messageType,
                                    'attachment' => $attachment,
                                    'attachment_type' => $attachmentType,
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
                                'phone_number_id' => $companyphoneId,
                                'customer_id' => $clientPhone,
                                'channel_id' => 1,
                                'customer_name' => $clientName,
                                'status' => "ON-BOT",
                            ]);

                            $conversationMessage = ConversationMessage::create([
                                'conversation_id' => $conversation->id,
                                'message' => $clientMessage,
                                'message_type' => $messageType,
                                'attachment' => $attachment,
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
                                    $this->sendMessage($clientPhone, $account->phone_number_id, $account->company_id, $nextStep->application_data, 'text', $fileUrl = NULL);
                                } elseif ($nextStep->application_type == "SendTextWait") {
                                    $chatbotLog = ChatBotLog::create([
                                        'conversation_id' => $conversation->id,
                                        'chat_flow_id' => $chatbot_flow->id,
                                        'current_flow_id' => $nextStep->id
                                    ]);
                                    $this->sendMessage($clientPhone, $account->phone_number_id, $account->company_id, $nextStep->application_data, 'text', $fileUrl = NULL);
                                    break;
                                } elseif ($nextStep->application_type == "Attachment") {
                                    $chatbotLog = ChatBotLog::create([
                                        'conversation_id' => $conversation->id,
                                        'chat_flow_id' => $chatbot_flow->id,
                                        'current_flow_id' => $nextStep->id
                                    ]);
                                    $fileAttachment = ChatBotFile::find($nextStep->application_data);
                                    if ($fileAttachment) {
                                        $type = $fileAttachment->file_type;

                                        $this->sendMessage($clientPhone, $account->phone_number_id, $account->company_id, $message = NULL, $type, $fileAttachment->name, $fileAttachment->file_url);
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
                        Log::critical("WhatsApp account not linked to flow!");
                    }
                } else {
                    Log::critical("WhatsApp account not found!");
                }
            } else {
                Log::critical("Phone number id not received from whatsapp!");
            }
        }
    }

    /**
     * It sends a message to a client using the WhatsApp Business API
     * 
     * @param clientPhone The phone number of the client you want to send the message to.
     * @param phoneNo_Id The phone number ID you got from the previous step.
     * @param company_id The company id of the company that owns the phone number
     * @param message The message you want to send.
     * @param type text, image, audio, video, document
     * @param fileName The name of the file you want to send.
     * @param fileUrl The URL of the file you want to send.
     * 
     * @return The response is being returned.
     */
    public function sendMessage($clientPhone, $phoneNo_Id, $company_id, $message = NULL, $type, $fileName = NULL, $fileUrl = NULL)
    {
        $url = "https://graph.facebook.com/v14.0/$phoneNo_Id/messages";
        $token = MetaAccessToken::where([
            'company_id' => $company_id,
            'active' => true
        ])->first();

        if ($type == 'text') {
            $appResponse = [
                "messaging_product" => "whatsapp",
                "recipient_type" => "individual",
                "to" => $clientPhone,
                "type" => "text",
                "text" => [
                    "preview_url" => false,
                    "body" => $message
                ]
            ];
        } else {
            $file = ChatMediaHelper::get_file($fileUrl);

            $fileID = ChatMediaHelper::upload_to_whatsapp($company_id, $phoneNo_Id, $file);
            $appResponse = array();
            if ($fileID) {
                ChatMediaHelper::delete_file($file);
                if ($type == 'image') {
                    $appResponse = [
                        "messaging_product" => "whatsapp",
                        "recipient_type" => "individual",
                        "to" => $clientPhone,
                        "type" => "image",
                        "image" => [
                            "id" => $fileID
                        ]
                    ];
                } elseif ($type == 'audio') {
                    $appResponse = [
                        "messaging_product" => "whatsapp",
                        "recipient_type" => "individual",
                        "to" => $clientPhone,
                        "type" => "audio",
                        "audio" => [
                            "id" => $fileID
                        ]
                    ];
                } elseif ($type == 'video') {
                    $appResponse = [
                        "messaging_product" => "whatsapp",
                        "recipient_type" => "individual",
                        "to" => $clientPhone,
                        "type" => "video",
                        "audio" => [
                            "id" => $fileID
                        ]
                    ];
                } elseif ($type == 'document') {
                    $appResponse = [
                        "messaging_product" => "whatsapp",
                        "recipient_type" => "individual",
                        "to" => $clientPhone,
                        "type" => "document",
                        "document" => [
                            "id" => $fileID,
                            "caption" => $fileName,
                            "filename" => $fileName
                        ]
                    ];
                }
            } else {
                Log::alert("Failed to upload whatsapp file!");
            }
        }
        if (!empty($appResponse)) {
            $response = Http::withToken($token->access_token)->post($url, $appResponse);
            if ($response->successful()) {
                return $response;
            } else {
                Log::critical(['Error sending WhatsApp Message' => $response->json()]);
                return null;
            }
        } else {
            Log::critical(['Empty appresponse']);
            return null;
        }
    }
}