<?php

namespace App\Http\Controllers\ChatDesk\chatqueue;

use App\Http\Controllers\Controller;
use App\Models\AutoReplyMessage;
use App\Models\Channel;
use App\Models\ChatQueue;
use App\Models\ChatQueueUser;
use App\Models\EmailQueue;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatQueueController extends Controller
{

    /**
     * It returns a list of all active channels
     * 
     * @return A JSON object containing all the channels that are active.
     */
    public function channels()
    {
        $data = Channel::where('active', 1)
            ->where('name', '!=', 'Voice')->get();

        return response()->json($data, 200);
    }

    public function associateEmail(Request $request)
    {
        $request->validate([
            'email_account_id' => 'required|exists:email_settings,id',
            'chat_queue_id' => 'required|exists:chat_queues,id',
        ]);

        $emailQueue = EmailQueue::updateOrCreate(
            ['email_id' => $request->email_account_id],
            ['chat_queue_id' => $request->chat_queue_id]
        );

        if ($emailQueue) {
            return response()->json([
                'success' => true,
                'message' => 'Email linked successfully to chat queue'
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Error linking email to chat queue'
        ], 502);
    }
    /**
     * It returns all the chat queues for the company.
     * 
     * @return A collection of ChatQueue objects with the users and autoreplyMessage relationships eager
     * loaded.
     */
    public function index()
    {
        $queues = ChatQueue::with('users', 'autoreplyMessage')
            ->where('company_id', Auth::user()->company_id)
            ->get();

        return response()->json($queues, 200);
    }

    /**
     * It validates the request, checks if the group belongs to the company, and then creates the chat
     * queue
     * 
     * @param Request request The request object
     * 
     * @return A JSON response with a message and a status code.
     */
    public function store(Request $request)
    {
        $request->validate([
            'group_id' => 'required|exists:groups,id',
            'name' => 'required|unique:chat_queues,name',
            'description' => 'required',
            'timeout' => 'required|integer|min:300',
            'max_open_conversation' => 'required|integer'
        ]);

        $group = Group::find($request->group_id);

        if ($group->company_id != Auth::user()->company_id) {
            return response()->json([
                'message' => 'Group does not belong to your company!'
            ], 401);
        }

        $chatQueue = ChatQueue::create([
            'company_id' => Auth::user()->company_id,
            'group_id' => $request->group_id,
            'name' => $request->name,
            'description' => $request->description,
            'timeout' => $request->timeout,
            'max_open_conversation' => $request->max_open_conversation
        ]);

        if ($chatQueue) {
            return response()->json(['message' => 'Chat Queue saved successfully!'], 200);
        }

        return response()->json(['message' => 'Error saving Chat Queue!'], 502);
    }

    /**
     * It adds a user to a chat queue
     * 
     * @param Request request The request object
     * 
     * @return A JSON response with a message and a status code.
     */
    public function addUser(Request $request)
    {
        $request->validate([
            'chat_queue_id' => 'required|exists:chat_queues,id',
            'user_id' => 'required|exists:users,id'
        ]);

        $user = User::find($request->user_id);

        $chatQueue = ChatQueue::find($request->chat_queue_id);

        if ($user->company_id != Auth::user()->company_id) {
            return response()->json(['message' => 'Not authorized to add users not in your company!'], 401);
        }

        if ($chatQueue->company_id != Auth::user()->company_id) {
            return response()->json(['message' => 'Not authorized to add users to Chat Queues not in your company!'], 401);
        }

        $chatQueueUser = ChatQueueUser::create([
            'user_id' => $request->user_id,
            'chat_queue_id' => $request->chat_queue_id
        ]);

        if ($chatQueueUser) {
            return response()->json(['message' => 'User added successfully to Chat Queue'], 200);
        }

        return response()->json(['message' => 'Error adding user to Chat Queue'], 502);
    }

    /**
     * This function removes a user from a chat queue
     * 
     * @param Request request The request object
     * 
     * @return A JSON response with a message and a status code.
     */
    public function removeUser(Request $request)
    {
        $request->validate([
            'chat_queue_id' => 'required|exists:chat_queue_users,chat_queue_id',
            'user_id' => 'required|exists:chat_queue_users,user_id'
        ]);

        $user = User::find($request->user_id);

        $chatQueue = ChatQueue::find($request->chat_queue_id);

        if ($user->company_id != Auth::user()->company_id) {
            return response()->json(['message' => 'Not authorized to remove users not in your company!'], 401);
        }

        if ($chatQueue->company_id != Auth::user()->company_id) {
            return response()->json(['message' => 'Not authorized to remove users to Chat Queues not in your company!'], 401);
        }

        $chatQueueUser = ChatQueueUser::where([
            'user_id' => $request->user_id,
            'chat_queue_id' => $request->chat_queue_id
        ])->first();

        if ($chatQueueUser) {
            $chatQueueUser->delete();
            return response()->json(['message' => 'User removed successfully from Chat Queue'], 200);
        }

        return response()->json(['message' => 'Error removeing user from Chat Queue'], 502);
    }

    /**
     * It activates a chat queue.
     * 
     * @param Request request The request object.
     * 
     * @return A JSON response with a message and a status code.
     */
    public function activateChatQueue(Request $request)
    {
        $request->validate([
            'chat_queue_id' => 'required|exists:chat_queues,id',
        ]);

        $chatQueue = ChatQueue::find($request->chat_queue_id);

        if ($chatQueue->company_id != Auth::user()->company_id) {
            return response()->json(['message' => 'Not authorized to activate this Chat Queue!'], 401);
        }

        $chatQueue->active = true;

        $chatQueue->save();

        return response()->json(['message' => 'Chat Queue activated successfully!'], 200);
    }

    /**
     * Deactivate a Chat Queue.
     * 
     * @param Request request The request object
     * 
     * @return A JSON response with a message and a status code.
     */
    public function deactivateChatQueue(Request $request)
    {
        $request->validate([
            'chat_queue_id' => 'required|exists:chat_queues,id',
        ]);

        $chatQueue = ChatQueue::find($request->chat_queue_id);

        if ($chatQueue->company_id != Auth::user()->company_id) {
            return response()->json(['message' => 'Not authorized to deactivate this Chat Queue!'], 401);
        }

        $chatQueue->active = false;

        $chatQueue->save();

        return response()->json(['message' => 'Chat Queue deactivated successfully!'], 200);
    }
    /**
     * It validates the request, checks if the user is authorized to add the autoreply message, and then
     * adds the autoreply message to the database
     * 
     * @param Request request The request object
     * 
     * @return A JSON response with a message and a status code.
     */

    public function addMessage(Request $request)
    {
        $request->validate([
            'chat_queue_id' => 'required|exists:chat_queues,id',
            'autoreply_message' => 'required'
        ]);

        $chatQueue = ChatQueue::find($request->chat_queue_id);

        if ($chatQueue->company_id != Auth::user()->company_id) {
            return response()->json(['message' => 'Not authorized!'], 401);
        }

        $autoMessage = AutoReplyMessage::updateOrCreate(
            ['chat_queue_id' => $request->chat_queue_id],
            ['autoreply_message' => $request->autoreply_message]
        );

        if ($autoMessage) {
            return response()->json(['message' => 'Chat Queue Autoreply Message added successfully'], 200);
        }

        return response()->json(['message' => 'Error saving Chat Queue autoreply message'], 502);
    }

    /**
     * It removes the autoreply message of a chat queue
     * 
     * @param Request request The request object
     * 
     * @return A JSON response with a message and a status code.
     */
    public function removeMessage(Request $request)
    {
        $request->validate([
            'chat_queue_id' => 'required|exists:chat_queues,id'
        ]);

        $chatQueue = ChatQueue::find($request->chat_queue_id);

        if ($chatQueue->company_id != Auth::user()->company_id) {
            return response()->json(['message' => 'Not authorized!'], 401);
        }

        $autoMessage = AutoReplyMessage::where([
            'chat_queue_id' => $request->chat_queue_id
        ]);

        if ($autoMessage) {
            $autoMessage->delete();
            return response()->json(['message' => 'Chat Queue Autoreply Message removed successfully'], 200);
        }

        return response()->json(['message' => 'Error removing Chat Queue autoreply message'], 502);
    }

    /**
     * If the chat queue doesn't belong to the user's company, return a 401 error. Otherwise, return the
     * chat queue
     * 
     * @param ChatQueue chatQueue This is the chat queue object that is passed to the function.
     * 
     * @return A JSON response with the chat queue object and a 200 status code.
     */
    public function show(ChatQueue $chatQueue)
    {
        if ($chatQueue->company_id != Auth::user()->company_id) {
            return response()->json(['message' => 'This chat queue does not belong to your company!'], 401);
        }

        return response()->json($chatQueue, 200);
    }

    /**
     * It updates the chat queue
     * 
     * @param Request request The request object.
     * @param ChatQueue chatQueue The chat queue object that is being updated.
     * 
     * @return A JSON response with a message and a status code.
     */
    public function update(Request $request, ChatQueue $chatQueue)
    {
        if ($chatQueue->company_id != Auth::user()->company_id) {
            return response()->json(['message' => 'Not authorized!'], 401);
        }

        $request->validate([
            'group_id' => 'required|exists:groups,id',
            'name' => 'required|unique:chat_queues,name,' . $chatQueue->id,
            'description' => 'required',
            'timeout' => 'required|integer|min:300',
            'max_open_conversation' => 'required|integer'
        ]);


        $group = Group::find($request->group_id);

        if ($group->company_id != Auth::user()->company_id) {
            return response()->json([
                'message' => 'Group does not belong to your company!'
            ], 401);
        }

        $chatQueue->update([
            'group_id' => $request->group_id,
            'name' => $request->name,
            'description' => $request->description,
            'timeout' => $request->timeout,
            'max_open_conversation' => $request->max_open_conversation
        ]);

        return response()->json(['message' => 'Chat Queue updated successfully!'], 200);
    }

    /**
     * > If the user is not authorized to delete the chat queue, return a 401 error. Otherwise, return a
     * 502 error
     * 
     * @param ChatQueue chatQueue This is the model that we are using for the API.
     * 
     * @return A JSON response with a message.
     */
    public function destroy(ChatQueue $chatQueue)
    {
        if ($chatQueue->company_id != Auth::user()->company_id) {
            return response()->json(['message' => 'Not authorized!'], 401);
        }

        return response()->json(['message' => 'Chat Queue deletion is disabled!'], 502);
    }
}