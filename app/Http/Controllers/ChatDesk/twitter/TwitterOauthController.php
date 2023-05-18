<?php

namespace App\Http\Controllers\ChatDesk\twitter;

use App\Http\Controllers\Controller;
use Abraham\TwitterOAuth\TwitterOAuth;
use Illuminate\Http\Request;

class TwitterOauthController extends Controller
{
    public function __construct()
    {
        $this->consumerKey = "4n5taLtdVuQItKEDoMGikTcax";
        $this->consumerSecret = "NzCAm2oaQbrVXUN3meXL7vFm25MAvQtYMlqbwXTB3aHBBcfwfc";
        $this->accessToken = "1580540819915710464-2o4enA8mFYvcnwszhTcaKeZCcaFCA8";
        $this->accessTokenSecret = "vtmMO7Py8zyZRqh9LagyWCVTfOKOf2hg1ms6ZMQZX3oer";

        $this->connection = new TwitterOAuth($this->consumerKey, $this->consumerSecret, $this->accessToken, $this->accessTokenSecret);
    }
    /**
     * registerWebhook.
     *
     * @return \Illuminate\Http\Response
     */
    public function registerWebhook()
    {

        $url = "https://ccbackenddev.goipcloud.co.ke/api/Webhook/Twitter";
        $content = $this->connection->post("account_activity/all/dev/webhooks", ["url" => $url]);

        return $content;
    }

    /**
     * // Subscribes user to a webhook.
     *
     * @return \Illuminate\Http\Response
     */
    public function subscribeToWebhook()
    {
        $content = $this->connection->post("account_activity/all/dev/subscriptions");

        return $content;
    }

    /**
     * // List registered Webhooks.
     *
     * @return \Illuminate\Http\Response
     */
    public function listWebhooks()
    {
        /* Build TwitterOAuth object with client credentials. */
        $connection = new TwitterOAuth($this->consumerKey, $this->consumerSecret);

        $request_token = $connection->oauth2('oauth2/token', ['grant_type' => 'client_credentials']);
        $connection = new TwitterOAuth($this->consumerKey, $this->consumerSecret, $request_token->access_token);
        $content = $connection->get("account_activity/all/webhooks");

        return $content;
    }

    /**
     * // Send Dm.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendMessage()
    {
        $data = [
            'event' => [
                'type' => 'message_create',
                'message_create' => [
                    'target' => [
                        'recipient_id' => 1580540819915710464
                    ],
                    'message_data' => [
                        'text' => 'Hello World!'
                    ]
                ]
            ]
        ];
        $content = $this->connection->post('direct_messages/events/new', $data, true);

        return $content;
    }
}
