<?php

namespace App\Console\Commands;

use App\Models\SmsMessage;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class checksmsdelivery extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:delivery';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and update sms delivery status';

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
        $messages = SmsMessage::whereHas('smsAccount', function (Builder $query) {
            $query->where('provider_id', 3);
        })->get();

        foreach ($messages as $key => $message) {

            if ($message->smsAccount->provider_id == 3) {
                $url = 'https://app.bongasms.co.ke/api/fetch-delivery';
                $data = [
                    'apiClientID' => $message->smsAccount->username,
                    'key' => $message->smsAccount->api_key,
                    'unique_id' => $message->message_id
                ];
                $response = Http::get($url, $data);

                if ($response['status'] == 222) {
                    $message->update([
                        'delivery_status' => $response['delivery_status_desc']
                    ]);
                }
            }
        }
    }
}