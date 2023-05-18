<?php

namespace App\Http\Controllers\call;

use App\Helpers\AdminDashboardHelper;
use App\Helpers\CallControlHelper;
use App\Helpers\CallHungupHelper;
use App\Http\Controllers\Controller;
use App\Models\ActiveAgentQueue;
use App\Models\CallAtribute;
use App\Models\CallLog;
use App\Models\CallServer;
use App\Models\CallTransferLog;
use App\Models\CDRTable;
use App\Models\DidList;
use App\Models\OutboundCallLogs;
use App\Models\OutboundSipDid;
use App\Models\QueueLog;
use App\Models\SipList;
use App\Models\User;
use App\Services\CustomerInformationService;
use App\Services\PhoneFormatterService;
use Auth;
use Illuminate\Http\Request;

class CallController extends Controller
{
    /**
     * It cancels a call.
     * 
     * @param Request request The request object
     */
    public function cancel_call(Request $request)
    {
        $agnet_abandoned = false;
        $sip = Auth::user()->sip->sip_id;
        $queue_log = QueueLog::where(["sip_id" => $sip])
            ->whereIn("status", ["ONCALL", "ONHOLD", "ONMUTE", "RINGAGENT", "CALL_FORWARDED"])
            ->latest()
            ->first();

        if ($queue_log) {
            $call_log = CallLog::find($queue_log->call_id);
            $trnsfer_log = CallTransferLog::where("agent_channel", $queue_log->channel_in_id)->latest()->first();
            if ($trnsfer_log) {
                $call_server = CallServer::where("server_name", $call_log->source)->first();

                $is_the_call_transfered = CallControlHelper::check_channel_update($trnsfer_log->forwarded_channel, $call_server->ip_address . ":" . $call_server->port);
                if ($is_the_call_transfered) {
                    logger("working on call transfer");
                    ActiveAgentQueue::where("user_id", $trnsfer_log->transfered_by)
                        ->update(["status" => "ONLINE"]);
                    CallLog::find($call_log->call_id)->update([
                        "call_status" => "ONCALL_FORWARDED",
                        "is_transfered_call" => true
                    ]);

                    QueueLog::where("call_id", $call_log->call_id)->update([
                        "status" => "ONCALL_FORWARDED",
                        "channel_in_id" => $trnsfer_log->forwarded_channel
                    ]);

                    CallAtribute::where(["attribute_name" => "CALLTIME", "call_id" => $call_log->call_id])
                        ->update(["end_time" => now()]);
                    CallAtribute::create([
                        "attribute_name" => "CALLTIMEFORWARD",
                        "call_id" => $call_log->call_id,
                        "start_time" => now()
                    ]);
                    CDRTable::where("call_id", $call_log->call_id)
                        ->where("call_type", "!=", "FORWARDED_CALL")
                        ->update(["desposition" => "ANSWERED"]);
                    CDRTable::where("call_id", $call_log->call_id)
                        ->where("call_type", "FORWARDED_CALL")
                        ->where("desposition", "FORWARD_RINGING")
                        ->update(["desposition" => "ONCALL_FORWARDED"]);
                    ActiveAgentQueue::where("sip_id", $sip)
                        ->update(["status" => "ONLINE"]);
                    CallControlHelper::unhold_channel($queue_log->call_id, $call_server->ip_address . ":" . $call_server->port);
                    CallControlHelper::add_to_bridge($trnsfer_log->transfer_bridge, $trnsfer_log->phone_channel, $call_server->ip_address . ":" . $call_server->port);
                    CallControlHelper::delete_channel($trnsfer_log->agent_channel, $call_server->ip_address . ":" . $call_server->port);

                    return response()->json(["Call ended!"], 200);
                } else {
                    $call_server = CallServer::where("server_name", $call_log->source)->first();
                    if ($queue_log->status == "RINGAGENT") {
                        try {
                            $agnet_abandoned = true;
                            CallControlHelper::delete_channel($queue_log->channel_in_id, $call_server->ip_address . ":" . $call_server->port);
                            // CallHungupHelper::Hungup($queue_log->call_id, true);
                        } catch (\Exception $ex) {
                            logger($ex);
                        }
                    } else {
                        try {
                            CallControlHelper::delete_bridge($queue_log->bridge_out_id, $call_server->ip_address . ":" . $call_server->port);
                            CallControlHelper::delete_channel($queue_log->channel_in_id, $call_server->ip_address . ":" . $call_server->port);
                            CallControlHelper::delete_channel($queue_log->call_id, $call_server->ip_address . ":" . $call_server->port);
                            // CallHungupHelper::Hungup($queue_log->call_id);
                        } catch (\Exception $ex) {
                            logger($ex);
                        }
                    }
                }
            } else {
                $call_server = CallServer::where("server_name", $call_log->source)->first();
                if ($queue_log->status == "RINGAGENT") {
                    try {
                        $agnet_abandoned = true;
                        CallControlHelper::delete_channel($queue_log->channel_in_id, $call_server->ip_address . ":" . $call_server->port);
                        // CallHungupHelper::Hungup($queue_log->call_id, true);
                    } catch (\Exception $ex) {
                        logger($ex);
                    }
                } else {
                    try {
                        CallControlHelper::delete_bridge($queue_log->bridge_out_id, $call_server->ip_address . ":" . $call_server->port);
                        CallControlHelper::delete_channel($queue_log->channel_in_id, $call_server->ip_address . ":" . $call_server->port);
                        CallControlHelper::delete_channel($queue_log->call_id, $call_server->ip_address . ":" . $call_server->port);
                        // CallHungupHelper::Hungup($queue_log->call_id);
                    } catch (\Exception $ex) {
                        logger($ex);
                    }
                }
            }
            AdminDashboardHelper::call_in_ivr($call_log->company_id);
            AdminDashboardHelper::agent_dashboard($queue_log->user_id);
            CallHungupHelper::Hungup($queue_log->call_id, $agnet_abandoned);
        }
    }

    /**
     * It hangs up the call
     * 
     * @return Json response is being returned as a JSON object.
     */
    public function hangup_click_tocall()
    {
        if (Auth::user()->sip) {
            $sip_id = Auth::user()->sip->sip_id;
            $cdr_log = CDRTable::whereIn("desposition", ["CALLING", "ONCALL", "RINGING", "FORWARD_RINGING", "ONCALL_FORWARDED"])
                ->where(function ($query) use ($sip_id) {
                    $query->where('sip_id', $sip_id)
                        ->orwhere('phone_number', $sip_id);
                })
                ->orderBy('id', 'DESC')
                ->first();

            if ($cdr_log) {
                logger("CDR TABLE");
                logger($cdr_log);

                if ($cdr_log->call_type == "FORWARDED_CALL") {
                    logger("Forwarded call");
                    logger($cdr_log->call_type);
                    $user_id = Auth::user()->id;
                    $transfered_call = CallTransferLog::where("transfered_to", $user_id)->latest()->first();
                    if ($transfered_call) {
                        $call_log = CallLog::find($transfered_call->phone_channel);
                        if (!$call_log) {
                            $call_log = CallLog::find($transfered_call->sip_channel);
                        }
                        $call_server_data = CallServer::where("server_name", $call_log->source)->first();
                        $call_server = $call_server_data->ip_address . ":" . $call_server_data->port;

                        $phone_channel = $transfered_call->phone_channel;
                        $sip_channel = $transfered_call->agent_channel;
                        $call_bridge = $transfered_call->original_bridge;
                        $transfered_bridge = $transfered_call->transfer_bridge;
                        $transfered_channel = $transfered_call->forwarded_channel;

                        if ($cdr_log->desposition == "ONCALL_FORWARDED") {
                            logger("trying to close the call");
                            try {
                                CallControlHelper::delete_bridge($transfered_bridge, $call_server);
                                CallControlHelper::delete_bridge($call_bridge, $call_server);
                            } catch (\Exception $ex) {
                                logger($ex);
                            }
                            try {
                                CallControlHelper::delete_channel($transfered_channel, $call_server);
                            } catch (\Exception $ex) {
                                logger($ex);
                            }
                            try {
                                CallControlHelper::delete_channel($phone_channel, $call_server);
                            } catch (\Exception $ex) {
                                logger($ex);
                            }
                            CallHungupHelper::Hungup($call_log->call_id, true);
                            ActiveAgentQueue::where("user_id", $user_id)->update(["status" => "ONLINE"]);
                            return response()->json(["Call canceled"]);
                        } else if ($call_log->call_status == "CALL_FORWARDED") {
                            ///merge the call back
                            CallControlHelper::unhold_channel($phone_channel, $call_server);
                            CallControlHelper::add_to_bridge($call_bridge, $phone_channel, $call_server);
                            CallControlHelper::add_to_bridge($call_bridge, $sip_channel, $call_server);
                            CallControlHelper::delete_bridge($transfered_bridge, $call_server);
                            CallControlHelper::delete_channel($transfered_channel, $call_server);
                            $cdr_to_update = CDRTable::where("call_id", $call_log->call_id)
                                ->where("call_type", "!=", "CALL_FORWARDED")
                                ->latest()
                                ->update(["desposition" => "ONCALL"]);
                            logger("upudated_data");
                            logger($cdr_to_update);
                            CDRTable::where("user_id", $user_id)
                                ->where("call_id", $call_log->call_id)
                                ->latest()
                                ->update(["desposition" => "ABANDONED"]);
                            ActiveAgentQueue::where("user_id", $user_id)->update(["status" => "ONLINE"]);
                            return response()->json(["Call canceled"]);
                        } else if ($call_log->call_status == "ONCALL") {
                            try {
                                CallControlHelper::delete_bridge($transfered_bridge, $call_server);
                            } catch (\Exception $ex) {
                                logger($ex);
                            }
                            try {
                                CallControlHelper::delete_channel($transfered_channel, $call_server);
                            } catch (\Exception $ex) {
                                logger($ex);
                            }
                            try {
                                CallControlHelper::delete_channel($phone_channel, $call_server);
                            } catch (\Exception $ex) {
                                logger($ex);
                            }
                            CallHungupHelper::Hungup($call_log->call_id, true);
                            ActiveAgentQueue::where("user_id", $user_id)->update(["status" => "ONLINE"]);
                            return response()->json(["Call canceled"]);
                        }
                    }
                } elseif ($cdr_log->call_type == "CLICKTOCALL") {
                    logger("Click to  call");
                    logger($cdr_log->call_type);

                    $outbound_log = OutboundCallLogs::where("sip_channel", $cdr_log->call_id)
                        ->latest()
                        ->first();

                    if ($outbound_log) {
                        $call_log = CallLog::find($cdr_log->call_id);
                        $call_server_data = CallServer::where("server_name", $call_log->source)->first();
                        $call_server = $call_server_data->ip_address . ":" . $call_server_data->port;
                        try {
                            if ($cdr_log->desposition == "ONCALL")
                                CallControlHelper::delete_bridge($outbound_log->sip_bridge, $call_server);
                        } catch (\Exception $ex) {
                            logger("click_to_call hangup bridge");
                            logger($ex);
                        }
                        try {
                            if ($outbound_log->phone_channel) {
                                CallHungupHelper::Hungup($cdr_log->call_id);
                                // CallControlHelper::delete_bridge($outbound_log->sip_bridge, $call_server);
                                CallControlHelper::delete_channel($outbound_log->phone_channel, $call_server);
                                CallControlHelper::delete_channel($outbound_log->sip_channel, $call_server);
                                return response()->json(["Call disconnected!"], 200);
                            }
                        } catch (\Exception $ex) {
                            logger("click_to_call hangup phone channel");
                            logger($ex);
                        }
                    }
                } else {

                    logger("Other call type");
                    logger($cdr_log->call_type);

                    $outbound_log = OutboundCallLogs::where("sip_channel", $cdr_log->call_id)
                        ->latest()
                        ->first();
                    if (!$outbound_log) {
                        $outbound_log = OutboundCallLogs::where("phone_channel", $cdr_log->call_id)
                            ->latest()
                            ->first();
                    }
                    if ($outbound_log) {
                        $call_log = CallLog::find($outbound_log->sip_channel);
                        $call_server_data = CallServer::where("server_name", $call_log->source)->first();
                        $call_server = $call_server_data->ip_address . ":" . $call_server_data->port;
                        try {
                            if ($cdr_log->desposition == "ONCALL")
                                CallControlHelper::delete_bridge($outbound_log->sip_bridge, $call_server);
                        } catch (\Exception $ex) {
                            logger("click_to_call hangup bridge");
                            logger($ex);
                        }
                        try {
                            if ($outbound_log->sip_channel) {
                                CallControlHelper::delete_channel($outbound_log->sip_channel, $call_server);
                                CallHungupHelper::Hungup($outbound_log->sip_channel);
                                return response()->json(["Call disconnected!"], 200);
                            }
                        } catch (\Exception $ex) {
                            logger("click_to_call hangup sip channel");
                            logger($ex);
                        }
                        try {
                            if ($outbound_log->phone_channel) {
                                logger("-------------------------");
                                logger("reaching to phone channel");
                                logger("-------------------------");

                                CallHungupHelper::Hungup($outbound_log->phone_channel);
                                CallControlHelper::delete_bridge($outbound_log->sip_bridge, $call_server);
                                CallControlHelper::delete_channel($outbound_log->phone_channel, $call_server);
                                CallControlHelper::delete_channel($outbound_log->sip_channel, $call_server);
                                return response()->json(["Call disconnected!"], 200);
                            }
                        } catch (\Exception $ex) {
                            logger("click_to_call hangup phone channel");
                            logger($ex);
                        }
                    }
                }
            } else {
                logger("-------------------------");
                logger("CDR LOG NOT FOUND!");
                logger("-------------------------");
            }
        } else {
            return response()->json(["call not found!"], 422);
        }
    }

    public function hungup_transfer()
    {
        $user_id = Auth::user()->id;
        $transfered_call = CallTransferLog::where("transfered_to", $user_id)->latest()->first();

        if ($transfered_call) {
            $call_log = CallLog::find($transfered_call->phone_channel);
            if (!$call_log) {
                $call_log = CallLog::find($transfered_call->sip_channel);
            }
            $call_server_data = CallServer::where("server_name", $call_log->source)->first();
            $call_server = $call_server_data->ip_address . ":" . $call_server_data->port;

            $phone_channel = $transfered_call->phone_channel;
            $sip_channel = $transfered_call->agent_channel;
            $call_bridge = $transfered_call->original_bridge;
            $transfered_bridge = $transfered_call->transfer_bridge;
            $transfered_channel = $transfered_call->forwarded_channel;

            if ($call_log->call_status == "CALL_FORWARDED") {
                ///merge the call back
                CallControlHelper::unhold_channel($phone_channel, $call_server);
                CallControlHelper::add_to_bridge($call_bridge, $phone_channel, $call_server);
                CallControlHelper::add_to_bridge($call_bridge, $sip_channel, $call_server);
                CallControlHelper::delete_bridge($transfered_bridge, $call_server);
                CallControlHelper::delete_channel($transfered_channel, $call_server);
                $cdr_to_update = CDRTable::where("user_id", "!=", $user_id)
                    ->where("call_id", $call_log->id)
                    ->where("call_type", "!=", "CALL_FORWARDED")
                    ->latest()
                    ->update(["desposition" => "ONCALL"]);

                CDRTable::where("user_id", $user_id)
                    ->where("call_id", $call_log->call_id)
                    ->latest()
                    ->update(["desposition" => "ABANDONED"]);
                ActiveAgentQueue::where("user_id", $user_id)->update(["status" => "ONLINE"]);
                return response()->json(["Call canceled"]);
            } else if ($call_log->call_status == "ONCALL") {
                try {
                    CallControlHelper::delete_bridge($transfered_bridge, $call_server);
                } catch (\Exception $ex) {
                    logger($ex);
                }
                try {
                    CallControlHelper::delete_channel($transfered_channel, $call_server);
                } catch (\Exception $ex) {
                    logger($ex);
                }
                try {
                    CallControlHelper::delete_channel($phone_channel, $call_server);
                } catch (\Exception $ex) {
                    logger($ex);
                }
                CallHungupHelper::Hungup($call_log->call_id, true);
                ActiveAgentQueue::where("user_id", $user_id)->update(["status" => "ONLINE"]);
                return response()->json(["Call canceled"]);
            }
        } else {
            return response()->json(["Call not found"], 422);
        }
    }

    /**
     * It accepts a call from the queue, and then sends it to the agent.
     * 
     * @param Request request The request object
     */
    public function accept_call(Request $request)
    {
        $request->validate([
            "sip" => "required",
            "phone_number" => "required"
        ]);
        $queue_log = QueueLog::where([
            "sip_id" => $request->sip,
            "status" => "CALLING"
        ])->orderBy("created_at", "DESC")->first();
        if ($queue_log) {
            $call_log = CallLog::find($queue_log->call_id);
            $call_server = CallServer::where(["server_name", $call_log->source])->first();
            $call_sent_response = CallControlHelper::call_phone_out($queue_log->caller_id, $call_log->did, $call_server->server_name, $call_server->ip_address . ":" . $call_server->port);

            $bridge_data = CallControlHelper::create_bridge($call_server->ip_address . ":" . $call_server->port);
            $bridge_id = json_decode($bridge_data[1])->id;
            CallControlHelper::add_to_bridge($bridge_id, json_decode($call_sent_response[1])->id, $call_server->ip_address . ":" . $call_server->port);

            $queue_log->channel_in_id = json_decode($call_sent_response[1])->id;
            $queue_log->bridge_id = $bridge_id;
            $queue_log->save();
        }
    }

    /**
     * If the call is inbound, mute the channel, if the call is outbound, mute the channel
     * 
     * @param Request request The request object
     * 
     * @return The response is a json object with a message property.
     */
    public function mute_call(Request $request)
    {
        $sip = $request->sip;
        $queue_log = QueueLog::where(["sip_id" => $sip, "status" => "ONCALL"])
            ->orwhere(["status" => "ONHOLD"])
            ->orwhere(["status" => "ONMUTE"])
            ->latest()
            ->first();
        if ($queue_log) {
            $is_call_muted = CallAtribute::where(["call_id" => $queue_log->call_id, "attribute_name" => "CALLMUTE", "end_time" => null])->latest()->first();
            $call_log = CallLog::find($queue_log->call_id);
            $call_server = CallServer::where("server_name", $call_log->source)->first();
            if (!$is_call_muted) {
                CallControlHelper::mute_channel($queue_log->channel_in_id, "in", $call_server->ip_address . ":" . $call_server->port);
                CallAtribute::create([
                    "call_id" => $queue_log->call_id,
                    "attribute_name" => "CALLMUTE",
                    "start_time" => now()
                ]);
                return response()->json([
                    'message' => 'muted'
                ], 200);
            } else {
                CallControlHelper::unmute_channel($queue_log->channel_in_id, "in", $call_server->ip_address . ":" . $call_server->port);
                CallAtribute::where("id", $is_call_muted->id)->update([
                    "end_time" => now()
                ]);
                return response()->json([
                    'message' => 'unmuted'
                ], 200);
            }
        } else {
            $cdr_log = CDRTable::where(["sip_id" => $sip])
                ->whereIn("desposition", ["ONHOLD", "ONCALL", "ONMUTE"])
                ->latest()
                ->first();
            if (!$cdr_log) {
                $cdr_log = CDRTable::where(["phone_number" => $sip])
                    ->whereIn("desposition", ["ONHOLD", "ONCALL", "ONMUTE"])
                    ->latest()
                    ->first();
            }
            if ($cdr_log) {
                $is_call_muted = CallAtribute::where(["call_id" => $cdr_log->call_id, "attribute_name" => "CALLMUTE", "end_time" => null])->first();
                $outbound_log = OutboundCallLogs::where("sip_channel", $cdr_log->call_id)->latest()->first();
                $channel_to_mute = "";
                if ($outbound_log) {
                    if ($outbound_log->sip_id == $sip) {
                        $channel_to_mute = $outbound_log->sip_channel;
                    } else if ($outbound_log->phone_number == $sip) {
                        $channel_to_mute = $outbound_log->phone_channel;
                    }
                }
                $call_log = CallLog::find($outbound_log->sip_channel);
                $call_server = CallServer::where("server_name", $call_log->source)->first();
                if (!$is_call_muted) {
                    CallControlHelper::mute_channel($channel_to_mute, "in", $call_server->ip_address . ":" . $call_server->port);
                    CallAtribute::create([
                        "call_id" => $cdr_log->call_id,
                        "attribute_name" => "CALLMUTE",
                        "start_time" => now()
                    ]);
                    return response()->json([
                        'message' => 'muted'
                    ], 200);
                } else {
                    CallControlHelper::unmute_channel($channel_to_mute, "in", $call_server->ip_address . ":" . $call_server->port);
                    CallAtribute::where("id", $is_call_muted->id)->update([
                        "end_time" => now()
                    ]);
                    return response()->json([
                        'message' => 'unmuted'
                    ], 200);
                }
            } else {
                $cdr_transfer = CDRTable::where(["sip_id" => $sip])
                    ->whereIn("desposition", ["ONCALL_FORWARDED"])
                    ->latest()
                    ->first();
                if ($cdr_transfer) {
                    $response = $this->mute_call_transfer();
                    return response()->json(["message" => $response], 200);
                }
            }
        }
    }

    /**
     * It checks if the user has a call transfered to him, if he does, it checks if the call is muted,
     * if it is not, it mutes it, if it is, it unmutes it.
     * </code>
     * 
     * @param Request request The request object.
     * 
     * @return The response is being returned as a JSON object.
     */
    public function mute_call_transfer()
    {
        $user_id = Auth::user()->id;
        $transfered_call = CallTransferLog::where("transfered_to", $user_id)->latest()->first();
        if ($transfered_call) {
            $call_log = CallLog::find($transfered_call->phone_channel);
            if (!$call_log) {
                $call_log = CallLog::find($transfered_call->sip_channel);
            }
            $is_call_muted = CallAtribute::where(["call_id" => $call_log->call_id, "attribute_name" => "CALLMUTE", "end_time" => null])->latest()->first();
            $call_server = CallServer::where("server_name", $call_log->source)->first();
            if (!$is_call_muted) {
                CallControlHelper::mute_channel($transfered_call->forwarded_channel, "in", $call_server->ip_address . ":" . $call_server->port);
                CallAtribute::create([
                    "call_id" => $call_log->call_id,
                    "attribute_name" => "CALLMUTE",
                    "start_time" => now()
                ]);
                return response()->json([
                    'message' => 'muted'
                ], 200);
            } else {
                CallControlHelper::unmute_channel($transfered_call->forwarded_channel, "in", $call_server->ip_address . ":" . $call_server->port);
                CallAtribute::where("id", $is_call_muted->id)->update([
                    "end_time" => now()
                ]);
                return response()->json([
                    'message' => 'unmuted'
                ], 200);
            }
        } else {
            return response()->json(["Call not found"]);
        }
    }

    /**
     * It checks if the call is on hold, if it is, it unholds it, if it isn't, it holds it
     * 
     * @param Request request The request object
     * 
     * @return <code>{
     *     "message": "hold"
     * }
     * </code>
     */
    public function hold_call(Request $request)
    {
        $sip = $request->sip;
        $queue_log = QueueLog::where(["sip_id" => $sip, "status" => "ONCALL"])
            ->orwhere(["status" => "ONHOLD"])
            ->orwhere(["status" => "ONMUTE"])
            ->latest()
            ->first();

        if ($queue_log) {
            $is_call_muted = CallAtribute::where(["call_id" => $queue_log->call_id, "attribute_name" => "CALLHOLD", "end_time" => null])
                ->latest()
                ->first();
            $call_log = CallLog::find($queue_log->call_id);
            $call_server = CallServer::where("server_name", $call_log->source)->first();
            if (!$is_call_muted) {
                CallControlHelper::hold_channel($queue_log->call_id, $call_server->ip_address . ":" . $call_server->port);
                CallAtribute::create([
                    "call_id" => $queue_log->call_id,
                    "attribute_name" => "CALLHOLD",
                    "start_time" => now()
                ]);
                return response()->json([
                    'message' => 'hold'
                ], 200);
            } else {
                CallControlHelper::unhold_channel($queue_log->call_id, $call_server->ip_address . ":" . $call_server->port);
                CallAtribute::where("id", $is_call_muted->id)->update([
                    "end_time" => now()
                ]);
                return response()->json([
                    'message' => 'oncall'
                ], 200);
            }
        } else {
            $cdr_log = CDRTable::where(["sip_id" => $sip])
                ->whereIn("desposition", ["ONHOLD", "ONCALL", "ONMUTE"])
                ->first();
            if (!$cdr_log) {
                $cdr_log = CDRTable::where(["phone_number" => $sip])
                    ->whereIn("desposition", ["ONHOLD", "ONCALL", "ONMUTE"])
                    ->first();
            }

            if ($cdr_log) {
                $is_call_muted = CallAtribute::where(["call_id" => $cdr_log->call_id, "attribute_name" => "CALLHOLD", "end_time" => null])->first();
                $outbound_log = OutboundCallLogs::where("sip_channel", $cdr_log->call_id)->first();
                $channel_to_mute = "";
                if ($outbound_log) {
                    if ($outbound_log->sip_id == $sip) {
                        $channel_to_mute = $outbound_log->phone_channel;
                    } else if ($outbound_log->phone_number == $sip) {
                        $channel_to_mute = $outbound_log->sip_channel;
                    }
                }
                $call_log = CallLog::find($outbound_log->sip_channel);
                $call_server = CallServer::where("server_name", $call_log->source)->first();
                if (!$is_call_muted) {
                    CallControlHelper::hold_channel($channel_to_mute, $call_server->ip_address . ":" . $call_server->port);
                    CallAtribute::create([
                        "call_id" => $cdr_log->call_id,
                        "attribute_name" => "CALLHOLD",
                        "start_time" => now()
                    ]);
                    return response()->json([
                        'message' => 'hold'
                    ], 200);
                } else {
                    CallControlHelper::unhold_channel($channel_to_mute, $call_server->ip_address . ":" . $call_server->port);
                    CallAtribute::where("id", $is_call_muted->id)->update([
                        "end_time" => now()
                    ]);
                    return response()->json([
                        'message' => 'oncall'
                    ], 200);
                }
            } else {
                $cdr_transfer = CDRTable::where(["sip_id" => $sip])
                    ->whereIn("desposition", ["ONCALL_FORWARDED"])
                    ->first();
                if ($cdr_transfer) {
                    $response = $this->call_hold_transfer();
                    return response()->json(["message" => $response], 200);
                }
            }
        }
    }

    /**
     * It checks if the call is on hold, if it is, it unholds it, if it isn't, it holds it.
     * </code>
     * 
     * @return The response is being returned as a JSON object.
     */
    public function call_hold_transfer()
    {
        $user_id = Auth::user()->id;
        $transfered_call = CallTransferLog::where("transfered_to", $user_id)->latest()->first();
        if ($transfered_call) {
            $call_log = CallLog::find($transfered_call->phone_channel);
            if (!$call_log) {
                $call_log = CallLog::find($transfered_call->sip_channel);
            }
            $is_call_muted = CallAtribute::where(["call_id" => $call_log->call_id, "attribute_name" => "CALLHOLD", "end_time" => null])->first();
            $call_server = CallServer::where("server_name", $call_log->source)->first();
            if (!$is_call_muted) {
                CallControlHelper::hold_channel($call_log->call_id, $call_server->ip_address . ":" . $call_server->port);
                CallAtribute::create([
                    "call_id" => $call_log->call_id,
                    "attribute_name" => "CALLHOLD",
                    "start_time" => now()
                ]);
                return 'hold';
            } else {
                CallControlHelper::unhold_channel($call_log->call_id, $call_server->ip_address . ":" . $call_server->port);
                CallAtribute::where("id", $is_call_muted->id)->update([
                    "end_time" => now()
                ]);
                return 'oncall';
            }
        } else {
            return response()->json(["Call not found"]);
        }
    }

    /**
     * It takes a phone number, formats it, and then sends it to a service that returns a customer's
     * information
     * 
     * @param Request request The request object
     * 
     * @return A JSON object with the following structure:
     * ```
     * {
     *     "caller_information": {
     *         "phone": "1234567890",
     *         "name": "John Doe",
     *         "address": "123 Main St",
     *         "city": "New York",
     *         "state": "NY",
     *         "zip": "10001",
     */
    public function get_caller_information(Request $request)
    {
        $customer_information = new CustomerInformationService();
        return $customer_information->get_call_information(PhoneFormatterService::format_phone($request->phone));
    }

    /**
     * It takes a phone number, finds the agent's sip id, finds the agent's dedicated outbound number,
     * finds the outbound call server, and then sends the call to the agent's sip id
     * 
     * @param Request request The request object
     * 
     * @return The response is a JSON object with the message "Call sent!" and a status code of 200.
     */
    public function click_to_call(Request $request)
    {
        $request->validate([
            "phone_number" => "required"
        ]);

        /* Checking if the phone number is valid. */
        if (preg_match('/^[0-9]{12}+$/', $request->phone_number)) {
            $phone = substr($request->phone_number, 3);
            $phone_number = '0' . $phone;
        } else if (preg_match('/^[0-9]{10}+$/', $request->phone_number)) {
            $phone_number = $request->phone_number;
        } else if (preg_match('/^[0-9]{9}+$/', $request->phone_number)) {
            $phone_number = '0' . $request->phone_number;
        } else {
            return response()->json(["Invalid number!"], 422);
        }

        $agent = Auth::user();
        $available_agent = ActiveAgentQueue::where("user_id", $agent->id)->first();
        if ($available_agent->status != "ONLINE") {
            return response()->json(["You are on another call!"], 422);
        } else if ($available_agent->sip_status != "ONLINE") {
            return response()->json(["You are offline!"], 422);
        }
        $did_to_call = OutboundSipDid::where("sip_id", Auth::user()->sip_id)->first();
        if (!$did_to_call) {
            return response()->json(["You don't have dedicated number for outbound call please contact the administrator!"], 422);
        }
        $did = DidList::find($did_to_call->did_id)->did;
        $call_server = CallServer::where("type", "OUTBOUND")->first();

        $channel_out_id = \App\Helpers\CallControlHelper::call_endpoint($available_agent->sip_id, $phone_number, $call_server->server_name, $call_server->ip_address . ":" . $call_server->port);

        $available_agent->update(["status" => "ONCALL"]);

        OutboundCallLogs::create([
            "sip_channel" => json_decode($channel_out_id[1])->id,
            "sip_id" => $available_agent->sip_id,
            "status" => "CALLING",
            "phone_number" => $phone_number,
            "source" => $call_server->server_name
        ]);
        CDRTable::create([
            'call_id' => json_decode($channel_out_id[1])->id,
            'phone_number' => $phone_number,
            // "bridge_id" => $bridge_id,
            'call_date' => date("y-m-d"),
            'call_time' => 0,
            "hold_time" => 0,
            "mute_time" => 0,
            'desposition' => "CALLING",
            'sip_id' => $available_agent->sip_id,
            "user_id" => $available_agent->user_id,
            "company_id" => Auth::user()->company_id,
            "call_type" => "CLICKTOCALL",
        ]);

        CallLog::create([
            "call_id" => json_decode($channel_out_id[1])->id,
            "did" => $did,
            "source" => $call_server->server_name,
            "caller_id" => $phone_number,
            "call_status" => "CALLING",
            "call_type" => "CLICKTOCALL",
            "company_id" => Auth::user()->company_id
        ]);
        AdminDashboardHelper::call_in_ivr(Auth::user()->company_id);
        return response()->json(["Call sent!"], 200);
    }

    /**
     * It takes a user_id or a sip_id and calls the sip_id
     * 
     * @param Request request The request object
     * 
     * @return The response is a JSON object with the key "Call sent!" and the value 200.
     */
    public function call_sip_to_sip(Request $request)
    {
        if (!$request->has('user_id') && !$request->has('sip')) {
            return response()->json(["Please input a user or SIP!"], 422);
        }

        $available_agent = ActiveAgentQueue::where("user_id", Auth::user()->id)->first();
        if ($available_agent->status != "ONLINE") {
            return response()->json(["You are on another call!"], 422);
        } else if ($available_agent->sip_status != "ONLINE") {
            return response()->json(["You are offline!"], 422);
        }

        $sip = null;
        if ($request->has('user_id')) {
            $sip_id = User::find($request->user_id);
            if ($sip_id) {
                $sip = SipList::find($sip_id->sip_id)->sip_id;
            } else {
                return response()->json(["The user you are trying to contact doesn't have SIP"], 422);
            }
        }

        if ($request->sip) {
            $sip_list = SipList::where("sip_id", $request->sip)->first();
            if (!$sip_list->user) {
                return response()->json(["The sip you entered isnot in use!"], 422);
            }

            if ($sip_list->user->company_id != Auth::user()->company_id) {
                return response()->json(["You can't call the sip!"], 401);
            }
            $sip = $request->sip;
        }
        $call_server = CallServer::where("type", "OUTBOUND")->first();

        $channel_out_id = \App\Helpers\CallControlHelper::call_endpoint($sip, $available_agent->sip_id, $call_server->server_name, $call_server->ip_address . ":" . $call_server->port);

        $available_agent->update(["status" => "ONCALL"]);

        OutboundCallLogs::create([
            "sip_channel" => json_decode($channel_out_id[1])->id,
            "sip_id" => $available_agent->sip_id,
            "status" => "CALLING",
            "phone_number" => $sip,
            "source" => $call_server->server_name
        ]);
        CDRTable::create([
            'call_id' => json_decode($channel_out_id[1])->id,
            'phone_number' => $sip,
            // "bridge_id" => $bridge_id,
            'call_date' => date("y-m-d"),
            'call_time' => 0,
            "hold_time" => 0,
            "mute_time" => 0,
            'desposition' => "CALLING",
            'sip_id' => $available_agent->sip_id,
            "user_id" => $available_agent->user_id,
            "company_id" => Auth::user()->company_id,
            "call_type" => "SIPCALL",
        ]);

        CallLog::create([
            "call_id" => json_decode($channel_out_id[1])->id,
            "did" => $sip,
            "source" => $call_server->server_name,
            "caller_id" => $available_agent->sip_id,
            "call_status" => "CALLING",
            "call_type" => "SIPCALL",
            "company_id" => Auth::user()->company_id
        ]);
        return response()->json(["Call sent!"], 200);
    }

    /**
     * It takes the current call, removes the agent from the bridge, holds the caller, rings the new
     * agent, and creates a new bridge with the caller and the new agent
     * 
     * @param Request request The request object.
     * 
     * @return The channel id of the call.
     */
    public function call_transfer(Request $request)
    {
        $request->validate([
            "user_id" => "required|exists:users,id"
        ]);

        $user_to_call = User::with("sip")->find($request->user_id);
        if ($user_to_call->company_id != Auth::user()->company_id) {
            return response()->json(["You are not allowed to call this user!"], 401);
        }

        if (!$user_to_call->sip) {
            return response()->json(["the user doesn't have SIP id"], 422);
        }

        $agent_activity = ActiveAgentQueue::where("user_id", $user_to_call->id)->first();

        if ($agent_activity->status != "ONLINE") {
            return response()->json(["the user is on another call!"], 422);
        }

        if ($agent_activity->sip_status != "ONLINE") {
            return response()->json(["The user is currently offline!"], 422);
        }

        $call_server = CallServer::where("type", "OUTBOUND")->first();
        // $channel_out_id = \App\Helpers\CallControlHelper::call_endpoint($user_to_call->sip->sip_id, Auth::user()->sip->sip_id, $call_server->server_name, $call_server->ip_address . ":" . $call_server->port);

        $current_call = CDRTable::where(["user_id" => Auth::user()->id, "desposition" => "ONCALL"])->latest()->first();


        $agent_channel = "";
        $caller_channel = "";
        $call_server_address = "";
        if ($current_call->call_type == "INBOUND") {
            $queue_log = QueueLog::where("call_id", $current_call->call_id)->first();
            $caller_channel = $queue_log->call_id;
            $agent_channel = $queue_log->channel_in_id;
            $bridge = $queue_log->bridge_out_id;
            $call_server = CallServer::where("type", "INBOUND")->latest()->first();
            $call_server_address = $call_server->ip_address . ":" . $call_server->port;
        } else {
            $outbound_log = OutboundCallLogs::where("sip_channel", $current_call->call_id)->latest()->first();
            $caller_channel = $outbound_log->phone_channel;
            $agent_channel = $outbound_log->sip_channel;
            $bridge = $outbound_log->sip_bridge;
            $call_server = CallServer::where("type", "OUTBOUND")->latest()->first();
            $call_server_address = $call_server->ip_address . ":" . $call_server->port;
        }
        // CallControlHelper::record_bridge()
        CallControlHelper::remove_channel_from_bridge($agent_channel, $bridge, $call_server_address);
        CallControlHelper::hold_channel($caller_channel, $call_server_address);
        CallControlHelper::ring_channel($agent_channel, $call_server_address);
        $channel_out_id = \App\Helpers\CallControlHelper::call_endpoint($user_to_call->sip->sip_id, Auth::user()->sip->sip_id, $call_server->server_name, $call_server->ip_address . ":" . $call_server->port);
        $bridge_data = \App\Helpers\CallControlHelper::create_bridge($call_server_address);
        $transfer_bridge_id = json_decode($bridge_data[1])->id;
        CallTransferLog::create([
            "agent_channel" => $agent_channel,
            "phone_channel" => $caller_channel,
            "forwarded_channel" => json_decode($channel_out_id[1])->id,
            "original_bridge" => $bridge,
            "transfer_bridge" => $transfer_bridge_id,
            "transfered_by" => Auth::user()->id,
            "transfered_to" => $request->user_id,
            "queue_id" => $current_call->queue_id
        ]);

        CallLog::find($current_call->call_id)->update([
            "call_status" => "CALL_FORWARDED"
        ]);

        QueueLog::where("call_id", $current_call->call_id)->update([
            "status" => "CALL_FORWARDED"
        ]);

        CDRTable::create([
            "call_id" => $current_call->call_id,
            "phone_number" => $current_call->phone_number,
            "bridge_id" => $transfer_bridge_id,
            "call_type" => "FORWARDED_CALL",
            "call_date" => now(),
            "call_time" => 0,
            "hold_time" => 0,
            "mute_time" => 0,
            "desposition" => "FORWARD_RINGING",
            "sip_id" => $user_to_call->sip->sip_id,
            "user_id" => $request->user_id,
            "company_id" => Auth::user()->company_id,
        ]);

        $current_call->update([
            "desposition" => "CALL_FORWARDED",
            "is_transfered_call" => true
        ]);
        ActiveAgentQueue::where("user_id", $request->user_id)->update(["status" => "RINGING"]);
        return response()->json(["Call forwarded!"], 200);
    }

    public function get_transfer_caller_information()
    {
        $user_id = Auth::user()->id;

        $forwarded_call = CDRTable::where([
            "user_id" => $user_id,
            "call_type" => "FORWARDED_CALL",
            "desposition" => "ONCALL_FORWARDED"
        ])->first();
        if ($forwarded_call) {
            $customer_information = new CustomerInformationService();
            return $customer_information->get_call_information(PhoneFormatterService::format_phone($forwarded_call->phone_number));
        } else {
            return null;
        }
    }
}