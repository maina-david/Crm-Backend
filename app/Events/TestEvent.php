<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TestEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public $channel_name, $eventname, $data;
    public function __construct($channel_name, $eventname, $data)
    {
        $this->channel_name = $channel_name;
        $this->eventname = $eventname;
        $this->data = $data;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel($this->channel_name); //channel name can either be user id for user specific data, company id for company specific data and group id for group specific data
    }



    public function broadcastAs()
    {
        return $this->eventname; // this is the event name for example sip-status-change, agent-dashboard-data-change
    }

    public function broadcastQueue()
    {
        return 'broadcastable';
    }

    public function broadcastWith()
    {
        //this returns the data to be broadcasted
        return $this->data;
    }
}
