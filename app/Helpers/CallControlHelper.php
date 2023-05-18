<?php

namespace App\Helpers;

use App\Models\CallLog;
use GuzzleHttp\Client as GuzzleClient;

class CallControlHelper
{
    /**
     * It takes a callid, an array of audio files, and a server address, and plays the audio files to
     * the callid
     * 
     * @param string callid The callid of the call you want to play the audio to.
     * @param array url_list This is an array of the audio files you want to play.
     * @param string server the IP address of the Asterisk server
     * 
     * @return The response code and the response body.
     */
    public static function playaudio(string $callid, $url_list, string $server)
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic Z29leHBlcmllbmNlOlRoZWtpbmdpc2NvbWluZ0AyMDIy',
        ];

        $client = new GuzzleClient([
            'headers' => $headers
        ]);
        $r = $client->request('POST', 'http://' . $server . '/ari/channels/' . $callid . '/play?media=sound:' . $url_list);
        $response = $r->getBody()->getContents();
        //return $response;   
        $response_code = $r->getStatusCode();
        ;
        return [$response_code, $response];
    }

    /**
     * It creates a bridge on the Asterisk server
     * 
     * @param string server The IP address of the Asterisk server
     * 
     * @return The response code and the response body.
     */
    public static function create_bridge(string $server)
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic Z29leHBlcmllbmNlOlRoZWtpbmdpc2NvbWluZ0AyMDIy',
        ];

        $client = new GuzzleClient([
            'headers' => $headers
        ]);
        $r = $client->request('POST', 'http://' . $server . '/ari/bridges?type=mixing,dtmf_events,proxy_media&technology=simple_bridge');
        $response = $r->getBody()->getContents();
        //return $response;   
        $response_code = $r->getStatusCode();
        ;
        return [$response_code, $response];
    }

    /**
     * It adds a channel to a bridge
     * 
     * @param bridge_id The bridge ID that you want to add the channel to.
     * @param channel_id The channel id of the channel to be added to the bridge
     * @param server the IP address of the Asterisk server
     * 
     * @return The response code and the response body.
     */
    public static function add_to_bridge(string $bridge_id, string $channel_id, string $server)
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic Z29leHBlcmllbmNlOlRoZWtpbmdpc2NvbWluZ0AyMDIy',
        ];

        $client = new GuzzleClient([
            'headers' => $headers
        ]);
        $r = $client->request('POST', 'http://' . $server . '/ari/bridges/' . $bridge_id . "/addChannel?channel=" . $channel_id);
        $response = $r->getBody()->getContents();
        //return $response;   
        $response_code = $r->getStatusCode();
        ;
        return [$response_code, $response];
    }

    /**
     * It takes a bridge_id and a server name and starts music on hold on the bridge
     * 
     * @param bridge_id The bridge ID of the bridge you want to start music on hold on.
     * @param server The IP address of the Asterisk server
     * 
     * @return The response code and the response body.
     */
    public static function start_bridge_moh(string $bridge_id, string $server)
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic Z29leHBlcmllbmNlOlRoZWtpbmdpc2NvbWluZ0AyMDIy',
        ];

        $client = new GuzzleClient([
            'headers' => $headers
        ]);
        $r = $client->request('POST', 'http://' . $server . '/ari/bridges/' . $bridge_id . "/moh");
        $response = $r->getBody()->getContents();
        //return $response;   
        $response_code = $r->getStatusCode();
        ;
        return [$response_code, $response];
    }

    /**
     * It calls an endpoint (phone number) and returns the response code and response
     * 
     * @param string endpoint_id The extension number of the endpoint you want to call
     * @param string phone_number The number to call
     * @param string server_name The name of the server you want to call the endpoint on.
     * @param string server the IP address of the server
     * 
     * @return The response code and the response body.
     */
    public static function call_endpoint(string $endpoint_id, string $phone_number, string $server_name, string $server)
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic Z29leHBlcmllbmNlOlRoZWtpbmdpc2NvbWluZ0AyMDIy',
        ];

        $client = new GuzzleClient([
            'headers' => $headers
        ]);
        $r = $client->request('POST', 'http://' . $server . '/ari/channels?endpoint=SIP/asterisk_firm_inbound/' . $endpoint_id . '&extension=' . $endpoint_id . '&context=asterisk_endpoint&priority=1&callerId=' . $phone_number);
        // 'http://165.22.112.71:8088/ari/channels?endpoint=IAX2/asteriskfirm1/0716597086&extension=0716597086&context=asterisk_firm&priority=1&callerId=0730672003'
        // 'http://165.22.112.71:8088/ari/channels?endpoint=IAX2/asteriskfirm1/10018&extension=10018&context=asterisk_firm&priority=1&callerId=100'
        $response = $r->getBody()->getContents();

        //return $response;   
        $response_code = $r->getStatusCode();
        ;
        return [$response_code, $response];
    }

    /**
     * It makes a POST request to the Asterisk server to initiate a call
     * 
     * @param string endpoint_id The phone number of the person you want to call
     * @param string phone_number The number you want to call
     * @param string server_name The name of the server you want to call from.
     * @param string server the IP address of the server
     */
    public static function call_phone_out(string $endpoint_id, string $phone_number, string $server_name, string $server)
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic Z29leHBlcmllbmNlOlRoZWtpbmdpc2NvbWluZ0AyMDIy',
        ];

        $client = new GuzzleClient([
            'headers' => $headers
        ]);
        $r = $client->request('POST', 'http://' . $server . '/ari/channels?endpoint=IAX2/asteriskfirm1/' . $endpoint_id . '&extension=' . $endpoint_id . '&context=asterisk_firm&priority=1&callerId=' . $phone_number);
        // 'http://165.22.112.71:8088/ari/channels?endpoint=IAX2/asteriskfirm1/0716597086&extension=0716597086&context=asterisk_firm&priority=1&callerId=0730672003'
        $response = $r->getBody()->getContents();

        //return $response;   
        $response_code = $r->getStatusCode();
        ;
        return [$response_code, $response];
    }

    /**
     * It removes a channel from a bridge
     * 
     * @param string channel_id The channel id of the channel you want to remove from the bridge
     * @param string bridge_id The ID of the bridge you want to remove the channel from.
     * @param string server The IP address of the Asterisk server
     * 
     * @return The response code and the response body.
     */
    public static function remove_channel_from_bridge(string $channel_id, string $bridge_id, string $server)
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic Z29leHBlcmllbmNlOlRoZWtpbmdpc2NvbWluZ0AyMDIy',
        ];

        $client = new GuzzleClient([
            'headers' => $headers
        ]);
        $r = $client->request('POST', 'http://' . $server . '/ari/bridges/' . $bridge_id . '/removeChannel?channel=' . $channel_id);
        $response = $r->getBody()->getContents();
        //return $response;   
        $response_code = $r->getStatusCode();
        ;
        return [$response_code, $response];
    }

    /**
     * It's a function that uses the Guzzle HTTP client to send a DELETE request to the Asterisk REST
     * Interface (ARI) to stop music on hold (MOH) on a bridge
     * 
     * @param string bridge_id The ID of the bridge you want to stop music on hold on.
     * @param string server The IP address of the Asterisk server
     * 
     * @return The response code and the response body.
     */
    public static function stop_bridge_moh(string $bridge_id, string $server)
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic Z29leHBlcmllbmNlOlRoZWtpbmdpc2NvbWluZ0AyMDIy',
        ];

        $client = new GuzzleClient([
            'headers' => $headers
        ]);
        $r = $client->request('DELETE', 'http://' . $server . '/ari/bridges/' . $bridge_id . "/moh");
        $response = $r->getBody()->getContents();
        //return $response;   
        $response_code = $r->getStatusCode();
        ;
        return [$response_code, $response];
    }

    /**
     * It deletes a bridge
     * 
     * @param string bridge_id The ID of the bridge you want to delete.
     * @param string server The IP address of the Asterisk server
     * 
     * @return The response code and the response body.
     */
    public static function delete_bridge(string $bridge_id, string $server)
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic Z29leHBlcmllbmNlOlRoZWtpbmdpc2NvbWluZ0AyMDIy',
        ];

        $client = new GuzzleClient([
            'headers' => $headers
        ]);
        $r = $client->request('DELETE', 'http://' . $server . '/ari/bridges/' . $bridge_id);
        $response = $r->getBody()->getContents();
        //return $response;   
        $response_code = $r->getStatusCode();
        ;
        return [$response_code, $response];
    }

    /**
     * It makes a POST request to the Asterisk server, passing the phone number, the DID, and the
     * server IP address
     * 
     * @param string phone_number The number you want to call
     * @param string did The phone number you want to call from
     * @param string server the IP address of the server
     * 
     * @return The response code and the response body.
     */
    public static function call_mobile(string $phone_number, string $did, string $server)
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic Z29leHBlcmllbmNlOlRoZWtpbmdpc2NvbWluZ0AyMDIy',
        ];
        $client = new GuzzleClient([
            'headers' => $headers
        ]);
        $r = $client->request('POST', 'http://' . $server . '/ari/channels?endpoint=IAX2/asteriskfirm1/' . $phone_number . '&extension=' . $phone_number . '&context=asterisk_firm&priority=1&callerId=' . $did);
        $response = $r->getBody()->getContents();
        //return $response;   
        $response_code = $r->getStatusCode();
        ;
        return [$response_code, $response];
    }

    /**
     * It checks if a channel exists on a given Asterisk server
     * 
     * @param string channel_id The channel ID of the channel you want to check.
     * @param string server the IP address of the Asterisk server
     * 
     * @return The response code and the response body.
     */
    public static function check_channel(string $channel_id, string $server)
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic Z29leHBlcmllbmNlOlRoZWtpbmdpc2NvbWluZ0AyMDIy',
        ];
        $client = new GuzzleClient([
            'headers' => $headers
        ]);
        $r = $client->request('GET', 'http://' . $server . '/ari/channels/' . $channel_id);
        $response = $r->getBody()->getContents();
        //return $response;   
        $response_code = $r->getStatusCode();
        ;
        return [$response_code, $response];
    }

    /**
     * It checks if a channel is still active by sending a GET request to the Asterisk ARI server
     * 
     * @param string channel_id The channel id of the channel you want to check
     * @param string server The IP address of the Asterisk server
     * 
     * @return The response is a JSON object.
     */
    public static function check_channel_update(string $channel_id, string $server)
    {
        try {
            $headers = [
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic Z29leHBlcmllbmNlOlRoZWtpbmdpc2NvbWluZ0AyMDIy',
            ];
            $client = new GuzzleClient([
                'headers' => $headers
            ]);
            $r = $client->request('GET', 'http://' . $server . '/ari/channels/' . $channel_id);
            $response = $r->getBody()->getContents();
            //return $response;   
            $response_code = $r->getStatusCode();
            ;
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * It deletes a channel from Asterisk
     * 
     * @param string channel_id The channel ID of the channel you want to delete.
     * @param string server The IP address of the Asterisk server
     * 
     * @return The response code and the response body.
     */
    public static function delete_channel(string $channel_id, string $server)
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic Z29leHBlcmllbmNlOlRoZWtpbmdpc2NvbWluZ0AyMDIy',
        ];
        $client = new GuzzleClient([
            'headers' => $headers
        ]);
        $r = $client->request('DELETE', 'http://' . $server . '/ari/channels/' . $channel_id);
        $response = $r->getBody()->getContents();
        //return $response;   
        $response_code = $r->getStatusCode();
        ;
        return [$response_code, $response];
    }

    /**
     * It takes a channel id, a direction (in or out), and a server name, and mutes the channel in the
     * specified direction
     * 
     * @param string channel_id The channel id of the channel you want to mute.
     * @param string direction in/out
     * @param string server The IP address of the Asterisk server
     * 
     * @return The response code and the response body.
     */
    public static function mute_channel(string $channel_id, string $direction, string $server)
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic Z29leHBlcmllbmNlOlRoZWtpbmdpc2NvbWluZ0AyMDIy',
        ];
        $client = new GuzzleClient([
            'headers' => $headers
        ]);
        $r = $client->request('POST', 'http://' . $server . '/ari/channels/' . $channel_id . '/mute?direction=' . $direction);
        $response = $r->getBody()->getContents();
        //return $response;   
        $response_code = $r->getStatusCode();
        ;
        return [$response_code, $response];
    }

    /**
     * It takes a channel id, a direction (inbound or outbound), and a server name, and then it unmutes
     * the channel
     * 
     * @param string channel_id The channel ID of the channel you want to mute.
     * @param string direction in or out
     * @param string server The IP address of the Asterisk server
     * 
     * @return The response code and the response body.
     */
    public static function unmute_channel(string $channel_id, string $direction, string $server)
    {

        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic Z29leHBlcmllbmNlOlRoZWtpbmdpc2NvbWluZ0AyMDIy',
        ];
        $client = new GuzzleClient([
            'headers' => $headers
        ]);
        $r = $client->request('DELETE', 'http://' . $server . '/ari/channels/' . $channel_id . '/mute?direction=' . $direction);
        $response = $r->getBody()->getContents();
        //return $response;   
        $response_code = $r->getStatusCode();
        ;
        return [$response_code, $response];
    }

    /**
     * It takes a channel id and a server name and puts the channel on hold.
     * 
     * @param string channel_id The channel id of the channel you want to hold
     * @param string server The IP address of the Asterisk server
     * 
     * @return The response code and the response body.
     */
    public static function hold_channel(string $channel_id, string $server)
    {

        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic Z29leHBlcmllbmNlOlRoZWtpbmdpc2NvbWluZ0AyMDIy',
        ];
        $client = new GuzzleClient([
            'headers' => $headers
        ]);
        $r = $client->request('POST', 'http://' . $server . '/ari/channels/' . $channel_id . '/hold');
        $response = $r->getBody()->getContents();
        //return $response;   
        $response_code = $r->getStatusCode();
        ;
        return [$response_code, $response];
    }

    /**
     * It takes a channel id and a server name as parameters and then sends a DELETE request to the
     * Asterisk ARI API to unhold the channel
     * 
     * @param string channel_id The channel id of the channel you want to unhold
     * @param string server The IP address of the Asterisk server
     * 
     * @return The response code and the response body.
     */
    public static function unhold_channel(string $channel_id, string $server)
    {

        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic Z29leHBlcmllbmNlOlRoZWtpbmdpc2NvbWluZ0AyMDIy',
        ];
        $client = new GuzzleClient([
            'headers' => $headers
        ]);
        $r = $client->request('DELETE', 'http://' . $server . '/ari/channels/' . $channel_id . '/hold');
        $response = $r->getBody()->getContents();
        //return $response;   
        $response_code = $r->getStatusCode();
        ;
        return [$response_code, $response];
    }

    /**
     * It takes a channel id, a bridge id, and a server name, and then records the bridge to a file
     * named after the channel id
     * 
     * @param string channel_id The channel ID of the channel to record.
     * @param string bridge_id The bridge ID of the bridge you want to record.
     * @param string server the IP address of the Asterisk server
     * 
     * @return The response code and the response body.
     */
    public static function record_bridge(string $channel_id, string $bridge_id, string $server)
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic Z29leHBlcmllbmNlOlRoZWtpbmdpc2NvbWluZ0AyMDIy',
        ];

        $client = new GuzzleClient([
            'headers' => $headers
        ]);
        $r = $client->request('POST', 'http://' . $server . '/ari/bridges/' . $bridge_id . '/record?name=' . $channel_id . '&format=wav');
        $response = $r->getBody()->getContents();
        //return $response;   
        $response_code = $r->getStatusCode();
        ;
        return [$response_code, $response];
    }

    /**
     * It takes a bridge ID, a list of URLs to play, and the server IP address, and plays the audio
     * files on the bridge
     * 
     * @param string bridge_id The bridge ID of the call you want to play the audio to.
     * @param string url_list This is the URL of the audio file you want to play.
     * @param string server The IP address of the Asterisk server
     * 
     * @return The response code and the response body.
     */
    public static function play_audio(string $bridge_id, string $url_list, string $server)
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic Z29leHBlcmllbmNlOlRoZWtpbmdpc2NvbWluZ0AyMDIy',
        ];

        $client = new GuzzleClient([
            'headers' => $headers
        ]);
        $r = $client->request('POST', 'http://' . $server . '/ari/bridges/' . $bridge_id . '/play?media=' . $url_list);
        $response = $r->getBody()->getContents();
        //return $response;   
        $response_code = $r->getStatusCode();
        ;
        return [$response_code, $response];
    }


    /**
     * It deletes a playback from the Asterisk server
     * 
     * @param string play_back_id The playback ID of the playback you want to delete.
     * @param string server The IP address of the Asterisk server
     * 
     * @return The response code and the response body.
     */
    public static function delete_playback(string $play_back_id, string $server)
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic Z29leHBlcmllbmNlOlRoZWtpbmdpc2NvbWluZ0AyMDIy',
        ];

        $client = new GuzzleClient([
            'headers' => $headers
        ]);
        $r = $client->request('DELETE', 'http://' . $server . '/ari/playbacks/' . $play_back_id);
        $response = $r->getBody()->getContents();
        //return $response;   
        $response_code = $r->getStatusCode();
        ;
        return [$response_code, $response];
    }

    /**
     * It dials a phone number from a sip channel
     * 
     * @param string phone_number The phone number to dial
     * @param string sip_channel SIP/asterisk_firm/phone_number
     * @param string phone_channel The channel ID of the phone call
     * @param string server the IP address of the server
     * 
     * @return The response code and the response body.
     */
    public static function dial_channel(string $phone_number, string $sip_channel, string $phone_channel, string $server)
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic Z29leHBlcmllbmNlOlRoZWtpbmdpc2NvbWluZ0AyMDIy',
        ];

        $client = new GuzzleClient([
            'headers' => $headers
        ]);
        // $r = $client->request('POST', 'http://' . $server . '/ari/channels/IAX2/asteriskfirm1/' . $sip_id . '/dial&caller=' . $phone_number);
        $r = $client->request('POST', 'http://' . $server . '/ari/channels/' . $sip_channel . '/dial?caller=' . $phone_channel);
        //  /channels/SIPchannelID/dial&caller=IAX2/asterisk_firm/phone_number
        $response = $r->getBody()->getContents();
        //return $response;   
        $response_code = $r->getStatusCode();
        ;
        return [$response_code, $response];
    }

    /**
     * It rings a channel
     * 
     * @param string channel_id The channel ID of the channel you want to ring.
     * @param string server The IP address of the Asterisk server
     * 
     * @return The response code and the response body.
     */
    public static function ring_channel(string $channel_id, string $server)
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic Z29leHBlcmllbmNlOlRoZWtpbmdpc2NvbWluZ0AyMDIy',
        ];

        $client = new GuzzleClient([
            'headers' => $headers
        ]);
        $r = $client->request('POST', 'http://' . $server . '/ari/channels/' . $channel_id . '/ring');

        $response = $r->getBody()->getContents();
        $response_code = $r->getStatusCode();
        ;
        return [$response_code, $response];
    }

    /**
     * It deletes a channel from the ring group
     * 
     * @param string channel_id The channel ID of the channel you want to stop ringing.
     * @param string server The IP address of the Asterisk server
     * 
     * @return The response code and the response body.
     */
    public static function delete_ring_channel(string $channel_id, string $server)
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic Z29leHBlcmllbmNlOlRoZWtpbmdpc2NvbWluZ0AyMDIy',
        ];

        $client = new GuzzleClient([
            'headers' => $headers
        ]);
        $r = $client->request('DELETE', 'http://' . $server . '/ari/channels/' . $channel_id . '/ring');

        $response = $r->getBody()->getContents();
        $response_code = $r->getStatusCode();
        ;
        return [$response_code, $response];
    }
}