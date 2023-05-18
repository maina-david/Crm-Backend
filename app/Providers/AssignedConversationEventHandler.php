<?php

namespace App\Providers;

use App\Providers\AssignedConversationEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Spatie\WebhookServer\WebhookCall;

class AssignedConversationEventHandler
{
    /**
     * Handle the event.
     *
     * @param  \App\Providers\AssignedConversationEvent  $event
     * @return void
     */
    public function handle(AssignedConversationEvent $event)
    {

        WebhookCall::create()
            ->url(env('FRONT_END_URL'))
            ->useSecret(env('WEBHOOK_SECRET_KEY'))
            ->payload([
                "user" => $event->user,
                "conversation" => $event->conversation,
                "assigned_conversation" => $event->assigned
            ])
            ->dispatch();
    }

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }
}