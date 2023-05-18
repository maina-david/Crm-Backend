<?php

namespace App\Helpers;

use App\Models\FaceBookPage;
use Abraham\TwitterOAuth\TwitterOAuth;
use App\Models\EmailSetting;
use App\Models\EmailTemplate;
use App\Models\MetaAccessToken;
use App\Models\TwitterAccount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;

class ReplyToConversation
{
    /**
     * It sends a WhatsApp message to a client
     * 
     * @param clientPhone The phone number of the client you want to send the message to.
     * @param phoneNo_Id The phone number ID you got from the previous step.
     * @param company_id The company id of the company that owns the phone number
     * @param message The message you want to send.
     * @param type The type of message you want to send. This can be text, image, audio, video, or
     * document.
     * @param fileUrl The URL of the file to be sent.
     * 
     * @return The response is being returned.
     */
    static function whatsapp($clientPhone, $phoneNo_Id, $company_id, $message = NULL, $type, $fileUrl = NULL)
    {
        $url = "https://graph.facebook.com/v15.0/$phoneNo_Id/messages";
        $token = MetaAccessToken::where([
            'company_id' => $company_id,
            'active' => true
        ])->first();

        if ($type == "text") {
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
            $trimmed = str_replace("https://goipspace.fra1.digitaloceanspaces.com/call_center/ChatMedia/", "", $fileUrl);
            $caption = explode(".", $trimmed);
            if ($fileID) {
                ChatMediaHelper::delete_file($file);
                if ($type == "image") {
                    $appResponse = [
                        "messaging_product" => "whatsapp",
                        "recipient_type" => "individual",
                        "to" => $clientPhone,
                        "type" => "image",
                        "image" => [
                            "id" => $fileID
                        ]
                    ];
                } elseif ($type == "audio") {
                    $appResponse = [
                        "messaging_product" => "whatsapp",
                        "recipient_type" => "individual",
                        "to" => $clientPhone,
                        "type" => "audio",
                        "audio" => [
                            "id" => $fileID
                        ]
                    ];
                } elseif ($type == "video") {
                    $appResponse = [
                        "messaging_product" => "whatsapp",
                        "recipient_type" => "individual",
                        "to" => $clientPhone,
                        "type" => "video",
                        "video" => [
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
                            "caption" => $caption[0],
                            "filename" => $caption[0]
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

    /**
     * It takes a customer ID, a page ID, and a message, and sends the message to the customer
     * 
     * @param customerID The ID of the customer you want to send the message to.
     * @param pageID The page ID of the Facebook page you want to send the message to.
     * @param message The message you want to send to the customer.
     * 
     * @return The response from the Facebook API.
     */
    public static function faceBook($customerID, $pageID, $message)
    {
        $token = FaceBookPage::where('page_id', $pageID)->first();

        $message = json_encode($message);

        $appmessage = str_replace(
            '"',
            '',
            $message
        );

        $url = "https://graph.facebook.com/v15.0/$pageID/messages";

        $data = "?recipient={'id':'$customerID'}&messaging_type=RESPONSE&message={'text':'$appmessage'}&access_token=$token->page_access_token";

        $response = Http::post($url . $data);

        if ($response->successful()) {
            return $response;
        } else {
            Log::critical(['Error sending Facebook Message' => $response->json()]);
            return null;
        }
    }

    /**
     * It takes a customer ID, a page ID, and an array of data, and sends a message to the customer
     * 
     * @param customerID The ID of the customer you want to send the message to.
     * @param page_id The ID of the Facebook page you want to send the message to.
     * @param appData This is the data that you want to send to the user.
     * 
     * @return The response from the API call.
     */
    public static function instagram($customerID, $page_id, $appData)
    {
        $token = FaceBookPage::find($page_id);

        $clientMessage = json_encode($appData);

        $appmessage = str_replace(
            '"',
            '',
            $clientMessage
        );

        $url = "https://graph.facebook.com/v15.0/me/messages";

        $data = "?recipient={'id':'$customerID'}&message={'text':'$appmessage'}&access_token=$token->page_access_token";

        $response = Http::post($url . $data);

        if ($response->successful()) {
            return $response;
        }

        Log::critical(['Error sending Instagram Message' => $response->json()]);

        return $response;
    }

    /**
     * It sends a direct message to a Twitter user
     * 
     * @param clientID The Twitter user ID of the person you want to send the message to.
     * @param accountID The ID of the account you want to send the message from.
     * @param message The message you want to send to the client.
     * 
     * @return The response from the Twitter API.
     */
    public static function twitter($clientID, $accountID, $message)
    {
        $account = TwitterAccount::where('account_id', $accountID)->first();
        $data = [
            'event' => [
                'type' => 'message_create',
                'message_create' => [
                    'target' => [
                        'recipient_id' => $clientID
                    ],
                    'message_data' => [
                        'text' => $message
                    ]
                ]
            ]
        ];

        $connection = new TwitterOAuth($account->consumer_key, $account->consumer_secret, $account->access_token, $account->access_token_secret);
        $content = $connection->post('direct_messages/events/new', $data, true);

        return $content;
    }

    /**
     * It sends an email to a recepient using the mailer of a client
     * 
     * @param client The name of the client you want to send the email from.
     * @param recepient The email address of the recepient
     * @param message The message to be sent.
     */
    public static function email($client, $recepient, $name, $subject, $message)
    {
        $account = EmailSetting::where('username', $client)->first();

        $template = EmailTemplate::where([
            'type' => 'CONVERSATION',
            'active' => true
        ])->first();

        // Use a default Blade file if the desired template does not exist
        if (!$template) {
            $template = (object) [
                'name' => 'RE: ' . $subject,
                'body' => View::make('emails.conversation.messages')->render()
            ];
        }

        // Replace placeholders with their corresponding values
        $data = [
            'name' => $name,
            'message' => $message,
            'valediction' => Auth::user()->name
        ];

        $body = preg_replace_callback('/\{\{(.*?)\}\}/', function ($match) use ($data) {
            return $data[$match[1]] ?? '';
        }, $template->body);

        try {

            // Create the Transport
            $transport = (new Swift_SmtpTransport($account->smtp_host, 25))
                ->setUsername($account->username)
                ->setPassword($account->password);

            // Create the Mailer using your created Transport
            $mailer = new Swift_Mailer($transport);

            // Create a mail message
            $mail = (new Swift_Message('RE: ' . $subject))
                ->setFrom([$account->username => $account->company->name])
                ->setTo([$recepient])
                ->setBody($body, 'text/html');

            // Send the message
            return  $mailer->send($mail);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}