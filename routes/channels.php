<?php

use App\Models\AssignedConversation;
use App\Models\ConversationMessage;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('assigned.conversation.{id}', function ($user, $id) {
    return $user->id === User::findOrNew($id)->id;
});

Broadcast::channel('new.conversation.message.{id}', function ($user, $id) {
    return $user->id === User::findOrNew($id)->id;
});

Broadcast::channel('unread.messages.conversation.{id}', function ($user, $id) {
    $assigned = AssignedConversation::where([
        'conversation_id' => $id,
        'status' => 'ON-GOING'
    ])->first();
    return $user->id ===  $assigned->agent_id;
});