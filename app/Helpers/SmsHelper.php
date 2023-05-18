<?php

use App\Models\SmsAccount;
use Illuminate\Support\Facades\Http;
use AfricasTalking\SDK\AfricasTalking;
use App\Models\SmsMessage;
use Illuminate\Support\Facades\Log;

/* Checking if the function exists, if it does not exist, it will create it. */

if (!function_exists('sendsms')) {

    /**
     * It takes in an account ID, a recipient and a message and sends the message to the recipient using
     * the account ID
     * 
     * @param accountID The ID of the SMS account you want to use to send the SMS.
     * @param recipient The phone number to which you want to send the message.
     * @param message The message to be sent.
     * 
     * @return A boolean value.
     */
    function sendsms($accountID, $recipient, $message)
    {
        $smsAccount = SmsAccount::find($accountID);

        if ($smsAccount) {
            $provider = $smsAccount->sms_provider->name;

            if ($provider == 'AfricasTalking') {
                $username = $smsAccount->username;
                $apiKey = $smsAccount->api_key;

                //Instantiate the AT class
                $AT       = new AfricasTalking($username, $apiKey);
                // Get sms services
                $sms      = $AT->sms();
                // Use the service
                try {
                    // Thats it, hit send and we'll take care of the rest
                    $result = $sms->send([
                        'to'      => $recipient,
                        'message' => $message
                    ]);

                    $ATresponse = (array) $result['data'];
                    $data = (array) $ATresponse['SMSMessageData'];
                    $recipients = (array) $data['Recipients'];

                    $res = (array) $recipients[0];

                    if ($res['statusCode'] == 101 || $res['statusCode'] == 102) {
                        $smsMessage = SmsMessage::create([
                            'company_id' => $smsAccount->company_id,
                            'sms_account_id' => $smsAccount->id,
                            'message_id' => $res['messageId'],
                            'message' => $message,
                            'recipient' => $recipient,
                            'status' => $res['status']
                        ]);

                        return response()->json($smsMessage, 200);
                    }
                    return FALSE;
                } catch (Throwable $th) {
                    Log::build([
                        'driver' => 'single',
                        'path' => storage_path('logs/smserrorlog.log'),
                    ])->alert($th);
                    return FALSE;
                }
            } elseif ($provider == 'AdvantaSms') {
                $url = 'https://quicksms.advantasms.com/api/services/sendsms';

                $data = [
                    'apikey' => $smsAccount->api_key,
                    'partnerID' => $smsAccount->username,
                    'message' => $message,
                    'shortcode' => $smsAccount->short_code,
                    'mobile' => $recipient
                ];

                $response = Http::post($url, $data);

                if ($response->successful()) {
                    $res = $response['responses'][0];
                    if ($res['response-code'] == 200) {
                        $smsMessage = SmsMessage::create([
                            'company_id' => $smsAccount->company_id,
                            'sms_account_id' => $smsAccount->id,
                            'message_id' => $res['messageid'],
                            'message' => $message,
                            'recipient' => $recipient,
                            'status' => $res['response-description']
                        ]);
                        return response()->json($smsMessage, 200);
                    }
                }
                if ($response->failed()) {
                    Log::build([
                        'driver' => 'single',
                        'path' => storage_path('logs/smserrorlog.log'),
                    ])->alert($response);
                    return FALSE;
                }
            } elseif ($provider == 'BongaSms') {
                $url = 'http://167.172.14.50:4002/v1/send-sms';
                $data = [
                    'apiClientID' => $smsAccount->username,
                    'key' => $smsAccount->api_key,
                    'secret' => $smsAccount->api_secret,
                    'txtMessage' => $message,
                    'MSISDN' => $recipient,
                ];

                $response = Http::post($url, $data);

                if ($response['status'] == 222) {
                    $smsMessage = SmsMessage::create([
                        'company_id' => $smsAccount->company_id,
                        'sms_account_id' => $smsAccount->id,
                        'message_id' => $response['unique_id'],
                        'message' => $message,
                        'recipient' => $recipient,
                        'status' => $response['status_message']
                    ]);
                    return response()->json($smsMessage, 200);
                }
                Log::build([
                    'driver' => 'single',
                    'path' => storage_path('logs/smserrorlog.log'),
                ])->alert($response);
                return FALSE;
            }
        }
        return FALSE;
    }
}
