<?php

namespace App\Handler;

use App\Models\CallServer;
use \Spatie\WebhookClient\ProcessWebhookJob;


class CallWebhookHandler_copy extends ProcessWebhookJob
{
    public function handle()
    {
        // $did = json_decode($this->webhookCall, true)['payload']['did'];
        //$callid = json_decode($this->webhookCall, true)['payload']['callid'];
        // return $this->webhookCall;
        $channel_id_out = $this->webhookCall['payload']['callid'];
        // $bridge = \App\Helpers\CallControlHelper::create_bridge();
        // $bridge_id = json_decode($bridge[1], true)['id'];
        // //$bridge_id = $bridge[1]['id']; 
        // $channel_added = \App\Helpers\CallControlHelper::add_to_bridge($bridge_id, $channel_id_out);
        $call_server = CallServer::where("server_name", $channel_id_out)->first();
        $play_audio = \App\Helpers\CallControlHelper::playaudio($channel_id_out, "https://goipspace.fra1.cdn.digitaloceanspaces.com/call_center/IVR_Files/working_hours.wav", $call_server->ip_address . ":" . $call_server->port);
        
        // $moh_start = \App\Helpers\CallControlHelper::start_bridge_moh($bridge_id);
        sleep(10);
        // $endpoint_channel = \App\Helpers\CallControlHelper::call_endpoint("6000", "0716597086");
        // $endpoint_channel_id = json_decode($endpoint_channel[1], true)['id'];
        // $bridge_endpoint = \App\Helpers\CallControlHelper::create_bridge();
        // $bridge_id_endpoint = json_decode($bridge_endpoint[1], true)['id'];
        // sleep(10);
        // $channel_added_endpoint = \App\Helpers\CallControlHelper::add_to_bridge($bridge_id_endpoint, $endpoint_channel_id);
        // $moh_start_endpoint = \App\Helpers\CallControlHelper::start_bridge_moh($bridge_id_endpoint);
        // sleep(10);

        // $remove_channel_endpoint = \App\Helpers\CallControlHelper::remove_channel_from_bridge($channel_id_out, $bridge_id);

        // $channel_added_endpoint_final = \App\Helpers\CallControlHelper::add_to_bridge($bridge_id_endpoint, $channel_id_out);
        // $moh_stop = \App\Helpers\CallControlHelper::stop_bridge_moh($bridge_id_endpoint);
        // $destroy_endpoint_bridge = $precallhandler->delete_bridge($bridge_id);
    }
}
