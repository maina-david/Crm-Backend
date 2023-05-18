<?php

namespace App\Http\Controllers\ChatDesk;

use App\Http\Controllers\Controller;
use App\Http\Resources\AgentConversationStatisticsResource;
use App\Http\Resources\QueueConversationStatisticsResource;
use App\Models\Channel;
use App\Models\ChatQueue;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{

    /**
     * It returns the number of all conversations, open conversations and closed conversations
     * 
     * @return the number of all conversations, open conversations and closed conversations.
     */
    public function conversations_statistics()
    {
        $allConversations = Conversation::where('company_id', Auth::user()->company_id)->count();

        $openConversations = Conversation::where('company_id', Auth::user()->company_id)
            ->where(function ($query) {
                $query->where('status', '=', 'ASSIGNED')
                    ->orWhere('status', '=', 'ON-GOING');
            })->count();

        $closedConversations = Conversation::where('company_id', Auth::user()->company_id)
            ->where('status', 'CLOSED')->count();

        $total_handling_time = Conversation::where([
            'company_id' => Auth::user()->company_id,
            'status' => 'CLOSED'
        ])->sum('handling_time');

        if ($closedConversations > 0) {
            $average_handling_time = $total_handling_time / $closedConversations;
        } else {
            $average_handling_time = 0;
        }

        return response()->json([
            'all_conversations' => $allConversations,
            'open_conversations' => $openConversations,
            'closed_conversations' => $closedConversations,
            'average_handling_time' => gmdate('H:i:s', $average_handling_time)
        ], 200);
    }

    /**
     * It returns the count of conversations for each channel
     * 
     * @return The channel name and the count of conversations for each channel.
     */
    public function channel_all_conversations_statistics()
    {
        $channels = Channel::where('name', '!=', 'Voice')
            ->where('active', true)
            ->get();

        /* Loading the count of conversations for each channel. */
        $channels->loadCount(['conversations' => function ($query) {
            $query->where('company_id', '=', Auth::user()->company_id);
        }]);

        return response()->json($channels, 200);
    }

    /**
     * It returns the count of conversations for each channel
     * 
     * @return The channel_open_conversations_statistics() method returns a JSON response containing the
     * channels and the count of conversations for each channel.
     */
    public function channel_open_conversations_statistics()
    {
        $channels = Channel::where('name', '!=', 'Voice')
            ->where('active', true)
            ->get();

        /* Loading the count of conversations for each channel. */
        $channels->loadCount(['conversations' => function ($query) {
            $query->where('company_id', '=', Auth::user()->company_id);
            $query->where(function ($q) {
                $q->where('status', '=', 'ASSIGNED')
                    ->orWhere('status', '=', 'ON-GOING');
            });
        }]);

        return response()->json($channels, 200);
    }

    /**
     * It returns the count of closed conversations for each channel
     * 
     * @return The channel_closed_conversations_statistics() method returns the count of closed
     * conversations for each channel.
     */
    public function channel_closed_conversations_statistics()
    {
        $channels = Channel::where('name', '!=', 'Voice')
            ->where('active', true)
            ->get();

        /* Loading the count of conversations for each channel. */
        $channels->loadCount(['conversations' => function ($query) {
            $query->where('company_id', '=', Auth::user()->company_id)
                ->Where('status', '=', 'CLOSED');
        }]);

        return response()->json($channels, 200);
    }

    /**
     * It returns the count of conversations for each queue
     * 
     * @return The response is a JSON object containing all the queues for the company, and the count of
     * conversations for each queue.
     */
    public function queue_all_conversations_statistics()
    {
        $queues = ChatQueue::where('company_id', Auth::user()->company_id)
            ->where('active', true)
            ->get();

        /* Loading the count of conversations for each queue. */
        $queues->loadCount(['conversations' => function ($query) {
            $query->where('company_id', '=', Auth::user()->company_id);
        }]);

        return response()->json(QueueConversationStatisticsResource::collection($queues), 200);
    }

    /**
     * It loads the count of conversations for each queue
     * 
     * @return A collection of QueueConversationStatisticsResource.
     */
    public function queue_open_conversations_statistics()
    {
        $queues = ChatQueue::where('company_id', Auth::user()->company_id)
            ->where('active', true)
            ->get();

        /* Loading the count of conversations for each queue. */
        $queues->loadCount(['conversations' => function ($query) {
            $query->where('company_id', '=', Auth::user()->company_id);
            $query->where(function ($q) {
                $q->where('conversations.status', '=', 'ASSIGNED')
                    ->orWhere('conversations.status', '=', 'ON-GOING');
            });
        }]);

        return response()->json(QueueConversationStatisticsResource::collection($queues), 200);
    }

    /**
     * It loads the count of conversations for each queue where the status is CLOSED
     * 
     * @return A collection of queues with the count of closed conversations for each queue.
     */
    public function queue_closed_conversations_statistics()
    {
        $queues = ChatQueue::where('company_id', Auth::user()->company_id)
            ->where('active', true)
            ->get();

        /* Loading the count of conversations for each queue. */
        $queues->loadCount(['conversations' => function ($query) {
            $query->where('company_id', '=', Auth::user()->company_id)
                ->Where('conversations.status', '=', 'CLOSED');
        }]);

        return response()->json(QueueConversationStatisticsResource::collection($queues), 200);
    }

    /**
     * It returns a list of all agents in the company, along with the number of conversations they have
     * 
     * @return A collection of users with the count of conversations for each queue.
     */
    public function agent_all_conversations_statistics()
    {
        $users = User::whereHas('conversations')->where('company_id', Auth::user()->company_id)
            ->get();

        /* Loading the count of conversations for each user. */
        $users->loadCount(['conversations' => function ($query) {
            $query->where('company_id', '=', Auth::user()->company_id);
        }]);

        return response()->json(AgentConversationStatisticsResource::collection($users), 200);
    }

    /**
     * It returns the count of conversations for each agent
     * 
     * @return A collection of users with the count of conversations for each queue.
     */
    public function agent_open_conversations_statistics()
    {
        $users = User::whereHas('conversations')->where('company_id', Auth::user()->company_id)
            ->get();

        /* Loading the count of conversations for each user. */
        $users->loadCount(['conversations' => function ($query) {
            $query->where('company_id', '=', Auth::user()->company_id);
            $query->where(function ($q) {
                $q->where('conversations.status', '=', 'ASSIGNED')
                    ->orWhere('conversations.status', '=', 'ON-GOING');
            });
        }]);

        return response()->json(AgentConversationStatisticsResource::collection($users), 200);
    }

    /**
     * It returns a list of all users that have conversations, and for each user, it returns the count of
     * conversations that are closed
     * 
     * @return A collection of users with the count of conversations for each queue.
     */
    public function agent_closed_conversations_statistics()
    {
        $users = User::whereHas('conversations')->where('company_id', Auth::user()->company_id)
            ->get();

        /* Loading the count of conversations for each user. */
        $users->loadCount(['conversations' => function ($query) {
            $query->where('company_id', '=', Auth::user()->company_id);
            $query->where('conversations.status', '=', 'CLOSED');
        }]);

        return response()->json(AgentConversationStatisticsResource::collection($users), 200);
    }
}