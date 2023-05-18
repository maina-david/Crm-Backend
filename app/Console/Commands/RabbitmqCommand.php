<?php

namespace App\Console\Commands;

use App\Events\TestEvent;
use App\Models\ActiveAgentQueue;
use Illuminate\Console\Command;
use Bschmitt\Amqp\Amqp;
use Bschmitt\Amqp\Consumer;

class RabbitmqCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rabbitmq:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will check the status of SIPs using rabbitmq';

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
        $amq = new Amqp();
        $amq->consume('SIP_Status_Queue', function ($message, $resolver) {
            if (json_decode($message->body)->method == "E_UL_CONTACT_INSERT" || json_decode($message->body)->method == "E_UL_CONTACT_DELETE" || json_decode($message->body)->method == "E_UL_AOR_INSERT" || json_decode($message->body)->method == "E_UL_AOR_DELETE" || json_decode($message->body)->method == "E_UL_CONTACT_UPDATE") {

                $sip_status_data = json_decode($message->body);
                $sip_string = explode("@", $sip_status_data->params->aor);
                $status = $sip_status_data->method;

                $updatearray = array();
                if ($status == "E_UL_AOR_DELETE") {
                    ActiveAgentQueue::where('sip_id', $sip_string[0])->update(["sip_status" => "DISCONNECTED"]);
                } else if ($status == "E_UL_CONTACT_INSERT" || $status == "E_UL_AOR_INSERT" || $status == "E_UL_CONTACT_UPDATE") {
                    ActiveAgentQueue::where('sip_id', $sip_string[0])->update(["sip_status" => "ONLINE"]);
                }
                $sip_status = ActiveAgentQueue::where("sip_id", $sip_string[0])->first(["user_id", "sip_status", "status", "penality"]);

                if ($sip_status) {
                    $event_response = event(new TestEvent(strval($sip_status->user_id), "agent_status", [
                        "status" => $sip_status
                    ]));
                    $resolver->acknowledge($message);
                    $resolver->stopWhenProcessed();
                }
            }
        });
        sleep(2);

        return 0;
    }
}