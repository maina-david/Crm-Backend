<?php

namespace App\Http\Controllers\ChatDesk;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\ChatQueue;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatReportController extends Controller
{

    /**
     * It returns a formatted array of conversations grouped by date and channel
     * 
     * @param Request request The request object
     */
    public function ChannelChatVolumeTrend(Request $request)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'frequency' => 'required|in:daily,weekly,monthly,yearly',
            'kpi' => 'required|in:TC,OC,CC,AHT,AQT'
        ]);

        $query = Conversation::where('company_id', Auth::user()->company_id);

        $query->when(request('frequency') == 'daily', function ($q) {
            return $q->selectRaw('HOUR(conversations.created_at) as date')
                ->wheredate('conversations.created_at', request('date'));
        });
        $query->when(request('frequency') == 'weekly', function ($q) {
            return $q->selectRaw('WEEKDAY(conversations.created_at) as date')
                ->whereRaw('WEEKDAY(conversations.created_at) = ?', [request('date')]);
        });
        $query->when(request('frequency') == 'monthly', function ($q) {
            $date = explode("-", request('date'));
            return $q->whereMonth('conversations.created_at', $date[1])
                ->whereYear('conversations.created_at', $date[0])
                ->selectRaw('DAY(conversations.created_at) as date');
        });
        $query->when(request('frequency') == 'yearly', function ($q) {
            return $q->whereYear('conversations.created_at', request('date'))
                ->selectRaw('MONTH(conversations.created_at) as date');
        });

        $query->when(request('kpi') == 'TC', function ($q) {
            return $q->selectRaw('COUNT(conversations.id) as conversations');
        });
        $query->when(request('kpi') == 'OC', function ($q) {
            return $q->where('conversations.status', "ONGOING")
                ->selectRaw('COUNT(conversations.id) as conversations');
        });
        $query->when(request('kpi') == 'CC', function ($q) {
            return $q->where('conversations.status', "CLOSED")
                ->selectRaw('COUNT(conversations.id) as conversations');
        });
        $query->when(request('kpi') == 'AHT', function ($q) {
            return $q->selectRaw('AVG(conversations.handling_time) as conversations');
        });
        $query->when(request('kpi') == 'AQT', function ($q) {
            return $q->selectRaw('AVG(conversations.handling_time) as conversations');
        });

        $conversations = $query->join('channels', 'conversations.channel_id', 'channels.id')
            ->selectRaw('channels.name')
            ->groupBy(['channels.name', 'date'])
            ->get();

        $channels = Channel::where('name', '!=', 'Voice')->get();

        return $this->formatDate($conversations, $request->frequency, $request->date, $channels, 'channels');
    }

    /**
     * It returns the chat volume trend for a given queue
     * 
     * @param Request request The request object
     */
    public function QueueChatVolumeTrend(Request $request)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'frequency' => 'required|in:daily,weekly,monthly,yearly',
            'kpi' => 'required|in:TC,OC,CC,AHT,AQT'
        ]);

        $query = Conversation::where('conversations.company_id', Auth::user()->company_id);

        $query->when(request('frequency') == 'daily', function ($q) {
            return $q->selectRaw('HOUR(conversations.created_at) as date')
                ->wheredate('conversations.created_at', request('date'));
        });
        $query->when(request('frequency') == 'weekly', function ($q) {
            return $q->selectRaw('WEEKDAY(conversations.created_at) as date')
                ->whereRaw('WEEKDAY(conversations.created_at) = ? ', [request('date')]);
        });
        $query->when(request('frequency') == 'monthly', function ($q) {
            $date = explode("-", request('date'));
            return $q->whereMonth('conversations.created_at', $date[1])
                ->whereYear('conversations.created_at', $date[0])
                ->selectRaw('DAY(conversations.created_at) as date');
        });
        $query->when(request('frequency') == 'yearly', function ($q) {
            return $q->whereYear('conversations.created_at', request('date'))
                ->selectRaw('MONTH(conversations.created_at) as date');
        });

        $query->when(request('kpi') == 'TC', function ($q) {
            return $q->selectRaw('COUNT(conversations.id) as conversations');
        });
        $query->when(request('kpi') == 'OC', function ($q) {
            return $q->where('conversations.status', "ONGOING")
                ->selectRaw('COUNT(conversations.id) as conversations');
        });
        $query->when(request('kpi') == 'CC', function ($q) {
            return $q->where('conversations.status', "CLOSED")
                ->selectRaw('COUNT(conversations.id) as conversations');
        });
        $query->when(request('kpi') == 'AHT', function ($q) {
            return $q->selectRaw('AVG(conversations.handling_time) as conversations');
        });
        $query->when(request('kpi') == 'AQT', function ($q) {
            return $q->selectRaw('AVG(conversations.handling_time) as conversations');
        });

        $conversations = $query->join('conversation_queues', 'conversations.id', 'conversation_queues.conversation_id')
            ->join('chat_queues', 'chat_queues.id', 'conversation_queues.chat_queue_id')
            ->selectRaw('chat_queues.name')
            ->groupBy(['chat_queues.name', 'date'])
            ->get();

        $queues = ChatQueue::where('company_id', Auth::user()->company_id)->get();

        return $this->formatDate($conversations, $request->frequency, $request->date, $queues, 'queues');
    }


    /**
     * It returns the chat volume trend for a specific agent
     * 
     * @param Request request The request object
     * 
     * @return the formatted data for the agent chat volume trend.
     */
    public function AgentChatVolumeTrend(Request $request)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'frequency' => 'required|in:daily,weekly,monthly,yearly',
            'kpi' => 'required|in:TC,OC,CC,AHT,AQT'
        ]);

        $query = Conversation::where('conversations.company_id', Auth::user()->company_id);

        $query->when(request('frequency') == 'daily', function ($q) {
            return $q->selectRaw('HOUR(conversations.created_at) as date')
                ->wheredate('conversations.created_at', request('date'));
        });
        $query->when(request('frequency') == 'weekly', function ($q) {
            return $q->selectRaw('WEEKDAY(conversations.created_at) as date')
                ->whereRaw('WEEKDAY(conversations.created_at) = ? ', [request('date')]);
        });
        $query->when(request('frequency') == 'monthly', function ($q) {
            $date = explode("-", request('date'));
            return $q->whereMonth('conversations.created_at', $date[1])
                ->whereYear('conversations.created_at', $date[0])
                ->selectRaw('DAY(conversations.created_at) as date');
        });
        $query->when(request('frequency') == 'yearly', function ($q) {
            return $q->whereYear('conversations.created_at', request('date'))
                ->selectRaw('MONTH(conversations.created_at) as date');
        });

        $query->when(request('kpi') == 'TC', function ($q) {
            return $q->selectRaw('COUNT(conversations.id) as conversations');
        });
        $query->when(request('kpi') == 'OC', function ($q) {
            return $q->where('conversations.status', "ONGOING")
                ->selectRaw('COUNT(conversations.id) as conversations');
        });
        $query->when(request('kpi') == 'CC', function ($q) {
            return $q->where('conversations.status', "CLOSED")
                ->selectRaw('COUNT(conversations.id) as conversations');
        });
        $query->when(request('kpi') == 'AHT', function ($q) {
            return $q->selectRaw('AVG(conversations.handling_time) as conversations');
        });
        $query->when(request('kpi') == 'AQT', function ($q) {
            return $q->selectRaw('AVG(conversations.handling_time) as conversations');
        });

        $conversations = $query->join('users', 'conversations.assigned_to', 'users.id')
            ->selectRaw('users.name')
            ->groupBy(['users.name', 'date'])
            ->get();

        $agents = User::where('company_id', Auth::user()->company_id)->get();

        return $this->formatDate($conversations, $request->frequency, $request->date, $agents, 'agents');
    }

    /**
     * It takes in a bunch of data, a frequency, a date, a list, and a type, and returns a response
     * 
     * @param data the data you want to format
     * @param frequency daily, weekly, monthly, yearly
     * @param date The date you want to get the data for.
     * @param list This is the list of various data type that you want to display on the chart.
     * @param type The type of data you want to get.
     * 
     * @return an array of data.
     */
    private function formatDate($data, $frequency, $date, $list, $type)
    {
        $response = [];
        if ($frequency == 'daily') {
            for ($i = 0; $i < 24; $i++) {
                $response["date"][$i] = sprintf("%02d", $i);
            }

            foreach ($list as $value) {
                for ($i = 1; $i <= 24; $i++) {
                    $response[$type][$value->name][$i] = 0;
                    foreach ($data as $chat) {
                        if ($value->name == $chat->name && $i == $chat->date) {
                            $response[$type][$value->name][$i] = number_format($chat->conversations, 2, '.', '');
                        }
                    }
                }
            }
        } elseif ($frequency == 'weekly') {
            $dayOfWeek = array("Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday");
            foreach ($list as $value) {
                for ($i = 0; $i < 7; $i++) {
                    $response["date"][$i] = $dayOfWeek[$i];
                    $response[$type][$value->name][$dayOfWeek[$i]] = number_format(0, 2, '.', '');
                    foreach ($data as $chat) {
                        if ($value->name == $chat->name && $i == $chat->date) {
                            $response[$type][$value->name][$dayOfWeek[$i]] = number_format($chat->conversations, 2, '.', '');
                        }
                    }
                }
            }
        } elseif ($frequency == 'monthly') {
            $day_split = explode("-", $date);
            for ($i = 1; $i <= cal_days_in_month(CAL_GREGORIAN, $day_split[1], $i); $i++) {
                $date = $i;
                $response["date"][$i] = $date;
            }
            foreach ($list as $value) {
                for ($i = 1; $i <= cal_days_in_month(CAL_GREGORIAN, $day_split[1], $i); $i++) {
                    $response[$type][$value->name][$i] = 0;
                    foreach ($data as $chat) {
                        if ($value->name == $chat->name && $i == $chat->date) {
                            $response[$type][$value->name][$i] = number_format($chat->conversations, 2, '.', '');
                        }
                    }
                }
            }
        } else if ($frequency == "yearly") {
            for ($i = 1; $i <= 12; $i++) {
                $date = date('F', mktime(0, 0, 0, $i, 10));
                $response["date"][$i] = $date;
            }

            foreach ($list as $value) {
                for ($i = 1; $i <= 12; $i++) {
                    $response[$type][$value->name][$i] = 0;
                    foreach ($data as $chat) {
                        if ($value->name == $chat->name && $i == $chat->date) {
                            $response[$type][$value->name][$i] = number_format($chat->conversations, 2, '.', '');
                        }
                    }
                }
            }
        }

        return $response;
    }
}