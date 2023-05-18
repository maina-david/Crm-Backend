<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use App\Models\ConversationMessage;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewConversationMessageEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The user that was assigned the conversation.
     *
     * @var \App\Models\User
     */
    public $user;
    public $conversationMessage;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, ConversationMessage $conversationMessage)
    {
        $this->user = $user;
        $this->conversationMessage = $conversationMessage;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('new.conversation.message.' . $this->user->id);
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'new.conversation.message.' . $this->user->id;
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'conversation_id' => $this->conversationMessage->conversation_id,
            'from' => $this->conversationMessage->conversation->customer_name,
            'message' => $this->conversationMessage
        ];
    }
}