<?php

namespace App\Http\Controllers\ChatDesk\conversation;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ConversationReportController extends Controller
{
    /**
     * It returns the total number of conversations, the number of closed conversations, the number of open
     * conversations and the average handling time of conversations
     * 
     * @param Request request This is the request object that is sent to the API.
     * 
     * @return The total number of conversations, the total number of closed conversations, the total
     * number of open conversations and the average handling time.
     */
    public function conversationVolumeTrend(Request $request)
    {
        $request->validate([
            'from' => 'nullable|date_format:Y-m-d|required_with:to',
            'to' => 'nullable|date_format:Y-m-d|required_with:from',
        ]);

        /* Checking if the request has the `from` and `to` parameters. If it does, it will return the total
            number of conversations, the total number of closed conversations, the total number of open
            conversations and the average handling time of conversations between the `from` and `to` dates. If
            it does not, it will return the total number of conversations, the total number of closed
            conversations, the total number of open conversations and the average handling time of conversations
            for today. */
        if ($request->has('from') && $request->has('to')) {
            /* Getting the total number of conversations between the `from` and `to` dates. */
            $total_conversations = Conversation::where('company_id', Auth::user()->company_id)
                ->whereBetween('created_at', [$request->from . " 00:00:00", $request->to . " 23:59:59"])
                ->count();
            /* Getting the total number of closed conversations between the `from` and `to` dates. */
            $closed_conversations = Conversation::where('company_id', Auth::user()->company_id)
                ->where('status', 'CLOSED')
                ->whereBetween('created_at', [$request->from . " 00:00:00", $request->to . " 23:59:59"])
                ->count();
            /* Getting the total number of open conversations between the `from` and `to` dates. */
            $open_conversations = Conversation::where('company_id', Auth::user()->company_id)
                ->where('status', 'ON-GOING')
                ->orWhere('status', 'ON-QUEUE')
                ->whereBetween('created_at', [$request->from . " 00:00:00", $request->to . " 23:59:59"])
                ->count();
            /* Getting the total number of seconds of all the closed conversations between the `from` and `to`
            dates. */
            $totalNumberOfSeconds = Conversation::where([
                'status' => 'CLOSED',
                'company_id' => Auth::user()->company_id
            ])->whereBetween('created_at', [$request->from . " 00:00:00", $request->to . " 23:59:59"])
                ->get()
                ->map(function ($conversation) {
                    return $conversation->closed_at->getTimestamp() - $conversation->created_at->getTimestamp();
                })
                ->sum();

            /* Getting the average handling time in minutes. */
            $averageInMinutes = $totalNumberOfSeconds / Conversation::where([
                'status' => 'CLOSED',
                'company_id' => Auth::user()->company_id
            ])
                ->whereBetween('created_at', [$request->from . " 00:00:00", $request->to . " 23:59:59"])
                ->count();
        } else {
            /* Getting the total number of conversations for today. */
            $total_conversations = Conversation::where('company_id', Auth::user()->company_id)
                ->whereDate('created_at', Carbon::today())
                ->count();
            /* Getting the total number of closed conversations for today. */
            $closed_conversations = Conversation::where('company_id', Auth::user()->company_id)
                ->where('status', 'CLOSED')
                ->whereDate('created_at', Carbon::today())
                ->count();
            /* Getting the total number of open conversations for today. */
            $open_conversations = Conversation::where('company_id', Auth::user()->company_id)
                ->where('status', 'ON-GOING')
                ->orWhere('status', 'ON-QUEUE')
                ->whereDate('created_at', Carbon::today())
                ->count();

            /* Getting the total number of seconds of all the closed conversations for today. */
            $totalNumberOfSeconds = Conversation::whereDate('closed_at', Carbon::today())
                ->where([
                    'status' => 'CLOSED',
                    'company_id' => Auth::user()->company_id
                ])->get()
                ->map(function ($conversation) {
                    return $conversation->closed_at->getTimestamp() - $conversation->created_at->getTimestamp();
                })
                ->sum();

            /* Getting the average handling time in minutes. */
            $averageInMinutes = $totalNumberOfSeconds / Conversation::where([
                'status' => 'CLOSED',
                'company_id' => Auth::user()->company_id
            ])
                ->whereDate('created_at', Carbon::today())
                ->count();
        }

        /* Converting the average handling time from minutes to seconds. */
        $average_handling_time = $averageInMinutes * 60;

        /* Returning the total number of conversations, the total number of closed conversations, the total
        number of open conversations and the average handling time of conversations. */
        return response()->json([
            'total_conversations' => $total_conversations,
            'closed_conversations' => $closed_conversations,
            'open_conversations' => $open_conversations,
            'average_handling_time' => $average_handling_time
        ], 200);
    }
}
