<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LoggedIn implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    private $type, $user;
    public function __construct($type, $user)
    {
        $this->type = $type;
        $this->user = $user;
    }

    public function broadcastWith()
    {
         //this returns the data to be broadcasted
        
         return [
            'id'       => "id",
            'name'     => "emmanuel",
            'username' => "emmanuel",
            'action'   => ucfirst(strtolower($this->type)),
            'on'       => now()->toDateTimeString(),
        ];
    }

    public function broadcastAs () {
        return 'activity-monitor'; // this is the event name for example sip-status-change, agent-dashboard-data-change
    }

    public function broadcastQueue () {
        return 'broadcastable';
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('activities'); //channel name can either be user id for user specific data, company id for company specific data and group id for group specific data
    }
}
