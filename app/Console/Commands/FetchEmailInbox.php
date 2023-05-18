<?php

namespace App\Console\Commands;

use App\Events\NewConversationMessageEvent;
use App\Models\AssignedConversation;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\ConversationQueue;
use App\Models\EmailQueue;
use App\Models\EmailSetting;
use App\Models\User;
use Webklex\IMAP\Facades\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchEmailInbox extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:emails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch email inbox from IMAP accounts';

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
        $emailaccounts = EmailSetting::get();

        if ($emailaccounts->count() > 0) {
            foreach ($emailaccounts as $emailaccount) {
                $account = [
                    'host'  => $emailaccount->imap_host,
                    'port'  => $emailaccount->imap_port,
                    'protocol'  => $emailaccount->incoming_transport, //might also use imap, [pop3 or nntp (untested)]
                    'encryption'    => $emailaccount->encryption, // Supported: false, 'ssl', 'tls', 'notls', 'starttls'
                    'validate_cert' => true,
                    'username' => $emailaccount->username,
                    'password' => $emailaccount->password,
                    'authentication' => null,
                    'proxy' => [
                        'socket' => null,
                        'request_fulluri' => false,
                        'username' => null,
                        'password' => null,
                    ],
                    "timeout" => 30,
                    "extensions" => []
                ];

                try {
                    /** @var \Webklex\PHPIMAP\Client $client */
                    $client = Client::make($account);
                    $client->connect();

                    //Get all Mailboxes
                    /** @var \Webklex\PHPIMAP\Support\FolderCollection $folders */
                    $folders = $client->getFolders();

                    //Loop through every Mailbox
                    /** @var \Webklex\PHPIMAP\Folder $folder */
                    foreach ($folders as $folder) {

                        //Get all Messages of the current Mailbox $folder
                        /** @var \Webklex\PHPIMAP\Support\MessageCollection $messages */
                        $messages = $folder->messages()->all()->get();


                        /** @var \Webklex\PHPIMAP\Message $message */
                        foreach ($messages as $message) {

                            $sender = $message->getFrom()[0]->mail;

                            $sender_name = $message->getFrom()[0]->personal;

                            $conversation = Conversation::where([
                                'customer_id' => $sender,
                                'company_id' => $emailaccount->company_id
                            ])->orderBy('id', 'DESC')->limit(1)->first();

                            $queue = EmailQueue::where('email_id', $emailaccount->id)->first();

                            if ($conversation) {
                                if ($conversation->status == 'CLOSED') {
                                    $existingConversation = ConversationMessage::where('message_id', $message->getUid())->first();
                                    if (!$existingConversation) {
                                        //create new conversation
                                        $newconversation = Conversation::create([
                                            'company_id' => $emailaccount->company_id,
                                            'phone_number_id' => $emailaccount->username,
                                            'customer_id' => $sender,
                                            'channel_id' => 5,
                                            'customer_name' => $sender_name,
                                            'subject' =>  $this->remove_emoji($message->getSubject()),
                                            'status' => $queue ? 'ON-QUEUE' : 'QUEUE-NOT-ASSIGNED',
                                        ]);

                                        $conversationMessage = ConversationMessage::create([
                                            'conversation_id' => $newconversation->id,
                                            'message_id' => $message->getUid(),
                                            'message' => $message->getTextBody(),
                                            'message_type' => 'text',
                                            'direction' => 'INCOMING'
                                        ]);

                                        if ($queue) {
                                            $conversationQueue = ConversationQueue::create([
                                                'conversation_id' => $newconversation->id,
                                                'chat_queue_id' => $queue->chat_queue_id
                                            ]);
                                        }
                                    }
                                } else {
                                    $conversationMessage = ConversationMessage::where([
                                        'message_id' => $message->getUid(),
                                        'conversation_id' =>  $conversation->id
                                    ])->first();

                                    if (!$conversationMessage) {
                                        $newMail = ConversationMessage::create([
                                            'conversation_id' => $conversation->id,
                                            'message_id' => $message->getUid(),
                                            'message' => $message->getTextBody(),
                                            'message_type' => 'text',
                                            'direction' => 'INCOMING'
                                        ]);

                                        $assignedConversation = AssignedConversation::where([
                                            'conversation_id' => $conversation->id,
                                        ])
                                            ->where(function ($query) {
                                                $query->where('status', '=', 'ASSIGNED')
                                                    ->orWhere('status', '=', 'ON-GOING');
                                            })
                                            ->first();

                                        if ($assignedConversation) {
                                            $user = User::find($assignedConversation->agent_id);

                                            NewConversationMessageEvent::dispatch($user, $newMail);
                                        }
                                    }
                                }
                            } else {
                                //create new conversation
                                $newconversation = Conversation::create([
                                    'company_id' => $emailaccount->company_id,
                                    'phone_number_id' => $emailaccount->username,
                                    'customer_id' => $sender,
                                    'channel_id' => 5,
                                    'customer_name' => $sender_name,
                                    'subject' => $this->remove_emoji($message->getSubject()),
                                    'status' => $queue ? 'ON-QUEUE' : 'QUEUE-NOT-ASSIGNED',
                                ]);

                                $conversationMessage = ConversationMessage::create([
                                    'conversation_id' => $newconversation->id,
                                    'message_id' => $message->getUid(),
                                    'message' => $message->getTextBody(),
                                    'message_type' => 'text',
                                    'direction' => 'INCOMING'
                                ]);

                                if ($queue) {
                                    $conversationQueue = ConversationQueue::create([
                                        'conversation_id' => $newconversation->id,
                                        'chat_queue_id' => $queue->chat_queue_id
                                    ]);
                                }
                            }

                            //Move the current Message to 'INBOX.read'
                            $message->move('INBOX.read');
                        }
                    }
                } catch (\Throwable $th) {
                    throw $th;
                }
            }
        }
    }

    function remove_emoji($string)
    {
        // Match Enclosed Alphanumeric Supplement
        $regex_alphanumeric = '/[\x{1F100}-\x{1F1FF}]/u';
        $clear_string = preg_replace($regex_alphanumeric, '', $string);

        // Match Miscellaneous Symbols and Pictographs
        $regex_symbols = '/[\x{1F300}-\x{1F5FF}]/u';
        $clear_string = preg_replace($regex_symbols, '', $clear_string);

        // Match Emoticons
        $regex_emoticons = '/[\x{1F600}-\x{1F64F}]/u';
        $clear_string = preg_replace($regex_emoticons, '', $clear_string);

        // Match Transport And Map Symbols
        $regex_transport = '/[\x{1F680}-\x{1F6FF}]/u';
        $clear_string = preg_replace($regex_transport, '', $clear_string);

        // Match Supplemental Symbols and Pictographs
        $regex_supplemental = '/[\x{1F900}-\x{1F9FF}]/u';
        $clear_string = preg_replace($regex_supplemental, '', $clear_string);

        // Match Miscellaneous Symbols
        $regex_misc = '/[\x{2600}-\x{26FF}]/u';
        $clear_string = preg_replace($regex_misc, '', $clear_string);

        // Match Dingbats
        $regex_dingbats = '/[\x{2700}-\x{27BF}]/u';
        $clear_string = preg_replace($regex_dingbats, '', $clear_string);

        return $clear_string;
    }
}