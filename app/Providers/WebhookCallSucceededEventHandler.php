<?php

namespace App\Providers;

use App\Models\AssignedConversation;
use App\Providers\WebhookCallSucceededEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class WebhookCallSucceededEventHandler
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Providers\WebhookCallSucceededEvent  $event
     * @return void
     */
    public function handle(WebhookCallSucceededEvent $event)
    {
        $payload = $event->payload["assigned_conversation"];
        /**
         * update records
         */
        $assignedConversation = AssignedConversation::find($payload->id);
        $assignedConversation->user_notified = true;
        $assignedConversation->save();
        return true;
    }
}