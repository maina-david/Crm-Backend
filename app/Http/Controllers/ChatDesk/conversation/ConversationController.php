<?php

namespace App\Http\Controllers\ChatDesk\conversation;

use App\Helpers\ReplyToConversation;
use App\Http\Controllers\Controller;
use App\Http\Resources\AssignedConversationResource;
use App\Http\Resources\ChannelConversationResource;
use App\Http\Resources\ChannelStats;
use App\Http\Resources\MessageResource;
use App\Models\AssignedConversation;
use App\Models\Channel;
use App\Models\ContactSocialAcct;
use App\Models\Conversation;
use App\Models\ConversationQueue;
use App\Models\ConversationMessage;
use App\Models\EmailSetting;
use App\Models\FaceBookPage;
use App\Models\InstagramAccount;
use App\Models\Interaction;
use App\Models\TwitterAccount;
use App\Models\WhatsappAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConversationController extends Controller
{

    /**
     * It gets all the channels except the voice channel, then it loads the count of conversations and
     * conversation messages for each channel
     * 
     * @return The conversation channels are being returned.
     */
    public function conversationChannels()
    {
        $channels = Channel::where('name', '!=', 'Voice')
            ->where('active', true)
            ->get();

        /* Loading the count of conversations for each channel. */
        $channels->loadCount(['conversations' => function ($query) {
            $query->where('company_id', '=', Auth::user()->company_id)
                ->where('assigned_to', '=', Auth::user()->id)
                ->where('status', '!=', 'CLOSED');
        }]);

        /* Loading the count of unread messages for each channel. */
        $channels->loadCount(['conversation_messages' => function ($query) {
            $query->where('conversation_messages.status', '=', 'UNREAD')
                ->where('conversations.assigned_to', '=', Auth::user()->id);
        }]);

        /* Returning a json response with the success and channels. */
        return response()->json([
            'success' => true,
            'channels' => ChannelStats::collection($channels)
        ], 200);
    }

    /**
     * It returns all the conversations for the company that the user is logged in to.
     * 
     * @return A collection of conversations.
     */
    public function index()
    {
        $conversations = Conversation::with('channel')
            ->where('company_id', Auth::user()->company_id)
            ->orderBy('id', 'DESC')
            ->get();

        return response()->json($conversations, 200);
    }

    /**
     * It returns all the assigned conversations of the logged in agent, and it also loads the count of
     * unread messages for each assigned conversation
     * 
     * @return A collection of assigned conversations.
     */
    public function assignedConversations()
    {
        /* Getting all the assigned conversations where the agent_id is the same as the logged in user's id and
        where the status is either ASSIGNED or ON-GOING. */
        $assignedConversations = AssignedConversation::where([
            'agent_id' => Auth::user()->id,
        ])
            ->where(function ($query) {
                $query->where('status', '=', 'ASSIGNED')
                    ->orWhere('status', '=', 'ON-GOING');
            })
            ->get();

        /* Loading the count of messages that are unread. */
        $assignedConversations->loadCount(['messages' => function ($query) {
            $query->where('status', 'UNREAD');
        }]);

        /* Returning the collection of assigned conversations. */
        return AssignedConversationResource::collection($assignedConversations);
    }

    /**
     * It returns all the assigned conversations of the logged in agent per channel
     * 
     * @param Request request The request object
     * 
     * @return A collection of assigned conversations for a specific channel.
     */
    public function assignedConversationsPerChannel(Request $request)
    {
        $request->validate([
            'channel_id' => 'required|exists:channels,id'
        ]);

        $assignedConversations = AssignedConversation::where([
            'agent_id' => Auth::user()->id,
            'channel_id' => $request->channel_id
        ])
            ->where(function ($query) {
                $query->where('status', '=', 'ASSIGNED')
                    ->orWhere('status', '=', 'ON-GOING');
            })
            ->get();

        $assignedConversations->loadCount(['messages' => function ($query) {
            $query->where('status', 'UNREAD');
        }]);

        return ChannelConversationResource::collection($assignedConversations);
    }

    /**
     * It returns all the messages of a conversation
     * 
     * @param Request request The request object.
     * 
     * @return A collection of messages
     */
    public function conversationMessages(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
        ], [
            'conversation_id.exists' => 'This conversation does not exist!'
        ]);

        $conversation = Conversation::find($request->conversation_id);

        if ($conversation->company_id != Auth::user()->company_id) {
            return response()->json(['message' => "This conversation does not belong to your company!"], 401);
        }

        $assignedConversation = AssignedConversation::where([
            'conversation_id' => $request->conversation_id,
            'agent_id' => Auth::user()->id
        ])->first();

        if ($assignedConversation) {

            $messages = ConversationMessage::query();

            $messages->where('conversation_id', $request->conversation_id);
            if ($request->searchMessage) {

                $messages->where('message', 'Like', '%' . $request->searchMessage . '%');
            }

            $conversationMessages = $messages->orderBy('id', 'ASC')->get();

            return MessageResource::collection($conversationMessages);
        } else {
            return response()->json(['message' => 'You are not assigned to this conversation'], 401);
        }
    }

    /**
     * It marks all messages in a conversation as read
     * 
     * @param Request request The request object.
     * 
     * @return A JSON response with a success message.
     */
    public function markAsRead(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
        ], [
            'conversation_id.exists' => 'This conversation does not exist!'
        ]);

        $conversation = Conversation::find($request->conversation_id);

        if ($conversation->company_id != Auth::user()->company_id) {
            return response()->json(['message' => "This conversation does not belong to your company!"], 401);
        }

        ConversationMessage::where('conversation_id', $request->conversation_id)
            ->update(['status' => 'READ']);

        return response()->json([
            'success' => true,
            'message' => 'Conversation messages marked as read successfully!'
        ], 200);
    }

    /**
     * It replies to a conversation
     * 
     * @param Request request The request object.
     * 
     * @return A JSON response with a message and a status code.
     */
    public function replyMessage(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'message_type' => 'required|in:text,image,document,audio,video',
            'message' => 'required_if:message_type,text',
            'file_url' => 'required_if:message_type,image,document,audio,video'
        ], [
            'conversation_id.exists' => 'This conversation does not exist!'
        ]);

        $conversation = Conversation::find($request->conversation_id);

        if ($conversation->company_id != Auth::user()->company_id) {
            return response()->json(['message' => "This conversation does not belong to your company!"], 401);
        }

        if ($conversation->status == 'CLOSED') {
            return response()->json(['message' => "This conversation is already closed!"], 401);
        }

        $conversationQueue = ConversationQueue::where([
            'conversation_id' => $request->conversation_id
        ])->first();

        if ($conversationQueue) {
            $assignedConversation = AssignedConversation::where([
                'conv_queue_id' => $conversationQueue->id,
                'conversation_id' => $request->conversation_id,
                'agent_id' => Auth::user()->id
            ])->first();

            if ($assignedConversation) {
                $assignedConversation->update([
                    'status' => 'ON-GOING'
                ]);
                $messageCount = ConversationMessage::where([
                    'conversation_id' => $request->conversation_id,
                    'agent_id' => Auth::user()->id
                ])->count();

                if ($messageCount == 0) {
                    $assignedConversation->update([
                        'first_response' => now()
                    ]);
                }
            } else {
                return response()->json(['message' => 'You are not assigned to this conversation'], 401);
            }
        } else {
            return response()->json(['message' => 'Conversation is not assigned to a queue'], 422);
        }

        $messageType = $request->message_type;

        $savemessage = new ConversationMessage();
        $savemessage->conversation_id = $request->conversation_id;
        $savemessage->message = $request->message;
        $savemessage->message_type = $request->message_type;
        $savemessage->message_level = 'ON-GOING';
        $savemessage->direction = 'OUTGOING';
        $savemessage->agent_id = Auth::user()->id;

        if ($messageType != 'text') {
            $savemessage->attachment = $request->file_url;
            $savemessage->attachment_type = $request->message_type;
        }

        $savemessage->save();

        if ($savemessage) {
            $message = $request->message . ".^" . Auth::user()->name;
            if ($conversation->channel_id == 1) {
                $company = WhatsappAccount::where('phone_number_id', $conversation->phone_number_id)->first();

                if ($company) {

                    if ($messageType == 'text') {
                        $response = ReplyToConversation::whatsApp($conversation->customer_id, $company->phone_number_id, $company->company_id, $message, $messageType, $fileUrl = NULL);
                    } else {
                        $response = ReplyToConversation::whatsApp($conversation->customer_id, $company->phone_number_id, $company->company_id, $message, $messageType, $request->file_url);
                    }
                    if ($response) {

                        $conversation->status = "ON-GOING";

                        $conversation->save();

                        return response()->json(['message' => 'Message sent successfully!'], 200);
                    } else {
                        $savemessage->status = 'FAILED';
                        $savemessage->save();
                        return response()->json(['message' => 'Error sending message!'], 502);
                    }
                }
            }
            if ($conversation->channel_id == 2) {
                $company = FaceBookPage::where('page_id', $conversation->phone_number_id)->first();

                if ($company) {
                    $response = ReplyToConversation::faceBook($conversation->customer_id, $conversation->phone_number_id, $message);

                    if ($response) {

                        $conversation->status = "ON-GOING";

                        $conversation->save();

                        return response()->json(['message' => 'Message sent successfully!'], 200);
                    } else {
                        $savemessage->status = 'FAILED';
                        $savemessage->save();
                        return response()->json(['message' => 'Error sending message!'], 502);
                    }
                }
            }
            if ($conversation->channel_id == 3) {
                $company = InstagramAccount::where('account_id', $conversation->phone_number_id)->first();

                if ($company) {
                    $response = ReplyToConversation::instagram($conversation->customer_id, $company->facebook_page_id, $message);

                    if ($response) {

                        $conversation->status = "ON-GOING";

                        $conversation->save();

                        return response()->json(['message' => 'Message sent successfully!'], 200);
                    } else {
                        $savemessage->status = 'FAILED';
                        $savemessage->save();
                        return response()->json(['message' => 'Error sending message!'], 502);
                    }
                }
            }
            if ($conversation->channel_id == 4) {
                $company = TwitterAccount::where('account_id', $conversation->phone_number_id)->first();

                if ($company) {
                    $response = ReplyToConversation::twitter($conversation->customer_id, $conversation->phone_number_id, $message);

                    if ($response) {

                        $conversation->status = "ON-GOING";

                        $conversation->save();

                        return response()->json(['message' => 'Message sent successfully!'], 200);
                    } else {
                        $savemessage->status = 'FAILED';
                        $savemessage->save();
                        return response()->json(['message' => 'Error sending message!'], 502);
                    }
                }
            }
            if ($conversation->channel_id == 5) {
                $company = EmailSetting::where('username', $conversation->phone_number_id)->first();

                if ($company) {
                    $response = ReplyToConversation::email($company->username, $conversation->customer_id, $conversation->customer_name, $conversation->subject, $request->message);

                    if ($response) {

                        $conversation->status = "ON-GOING";

                        $conversation->save();

                        return response()->json(['message' => 'Message sent successfully!'], 200);
                    } else {
                        $savemessage->status = 'FAILED';
                        $savemessage->save();
                        return response()->json(['message' => 'Error sending message!'], 502);
                    }
                }
            }
        }
    }

    /**
     * It returns a conversation to the queue
     * 
     * @param Request request The request object.
     * 
     * @return The conversation is being returned to the queue.
     */
    public function returnToQueue(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
        ], [
            'conversation_id.exists' => 'This conversation does not exist.'
        ]);

        $conversationQueue = ConversationQueue::where([
            'conversation_id' => $request->conversation_id
        ])->first();

        if ($conversationQueue) {
            $assignedConversation = AssignedConversation::where('agent_id', '=', Auth::user()->id)
                ->where('conv_queue_id', '=', $conversationQueue->id)
                ->where(
                    function ($query) {
                        return $query
                            ->where('status', '=', 'ASSIGNED')
                            ->orWhere('status', '=', 'ON-GOING');
                    }
                )
                ->first();

            if ($assignedConversation) {
                $assignedConversation->update([
                    'status' => 'TRANSFERRED'
                ]);

                $conversation = Conversation::find($request->conversation_id);

                $conversation->status = 'ON-QUEUE';

                $conversation->assigned_to = NULL;

                $conversation->save();

                $conversationQueue->status = 'UNASSIGNED';

                $conversationQueue->save();

                return response()->json(['message' => 'Conversation returned to queue successfully!'], 200);
            }
            return response()->json(['message' => 'You are not assigned this Conversation!'], 401);
        }
    }

    /**
     * It closes a conversation
     * 
     * @param Request request The request object.
     * 
     * @return A JSON response with a message and a status code.
     */
    public function closeConversation(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
        ], [
            'conversation_id.exists' => 'This conversation does not exist.'
        ]);

        $conversationID = $request->conversation_id;

        $conversationQueue = ConversationQueue::where([
            'conversation_id' => $conversationID
        ])->first();

        if ($conversationQueue) {
            $assignedConversation = AssignedConversation::where([
                'agent_id' => Auth::user()->id,
                'conversation_id' => $conversationID,
                'conv_queue_id' => $conversationQueue->id
            ])
                ->where(
                    function ($query) {
                        return $query
                            ->where('status', '=', 'ASSIGNED')
                            ->orWhere('status', '=', 'ON-GOING');
                    }
                )
                ->first();

            if ($assignedConversation) {
                $assignedConversation->update([
                    'status' => 'CLOSED',
                    'closed_at' => now()
                ]);

                $conversation = Conversation::find($conversationID);

                $conversation->status = 'CLOSED';

                $conversation->closed_at = now();

                $conversation->save();

                $onqueueTime = $conversationQueue->created_at;

                $closedatTime = $conversation->closed_at;

                $handling_time = $onqueueTime->diffInSeconds($closedatTime);

                $conversation->update(['handling_time' => $handling_time]);

                $interactionCheck = Interaction::where([
                    'company_id' => $conversation->company_id,
                    'channel_id' => $conversation->channel_id,
                    'interaction_reference' => $conversation->id,
                    'interaction_type' => 'chat'
                ])->first();

                if (!$interactionCheck) {
                    $interaction = Interaction::create([
                        'company_id' => $conversation->company_id,
                        'channel_id' => $conversation->channel_id,
                        'interaction_reference' => $conversation->id,
                        'interaction_type' => 'chat'
                    ]);
                }

                return response()->json(['message' => 'Conversation closed successfully!'], 200);
            }

            return response()->json(['message' => 'You are not assigned this Conversation!'], 401);
        }
    }

    /**
     * This function associates a contact to a social account
     * 
     * @param Request request 
     * 
     * @return A JSON response with a message of "associated successfully!"
     */
    public function associate_contact_to_account(Request $request)
    {
        $request->validate([
            "contact_id" => "required|exists:contacts,id",
            "channel_id" => "required|exists:channels,id",
            "social_account_id" => "required"
        ]);

        /* Creating a new contact social account. */
        ContactSocialAcct::create([
            "contact_id" => $request->contact_id,
            "social_account" => $request->social_account_id,
            "channel_id" => $request->channel_id
        ]);

        return response()->json([
            'message' => 'Contact associated to account successfully!'
        ], 200);
    }
}