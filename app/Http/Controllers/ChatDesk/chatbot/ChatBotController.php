<?php

namespace App\Http\Controllers\ChatDesk\chatbot;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChatBotAccountResource;
use App\Http\Resources\FaceBookAccountResource;
use App\Http\Resources\InstagramAccountResource;
use App\Http\Resources\TwitterAccountResource;
use App\Http\Resources\WhatsappAccountResource;
use App\Models\ChatBot;
use App\Models\ChatBotAccountPivot;
use App\Models\ChatBotFile;
use App\Models\ChatBotFlow;
use App\Models\ChatBotLink;
use App\Models\ChatBotLog;
use App\Models\ChatBotUi;
use App\Models\ChatQueue;
use App\Models\FaceBookPage;
use App\Models\InstagramAccount;
use App\Models\InstagramPage;
use App\Models\TwitterAccount;
use App\Models\WhatsappAccount;
use App\Rules\ChatBotUniqueName;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChatBotController extends Controller
{
    /**
     * Create new ChatBot.
     *
     * @return \Illuminate\Http\Response
     */
    public function createChatbot(Request $request)
    {
        $request->validate([
            'name' => 'required|', new ChatBotUniqueName,
            'description' => 'required'
        ]);

        $chatBot = ChatBot::create([
            'name' => $request->name,
            'description' => $request->description,
            'company_id' => Auth::user()->company_id
        ]);

        if ($chatBot) {

            return response()->json([
                'message' => 'Successfully saved',
                'chatbot' => $chatBot
            ], 200);
        }

        return response()->json(['message' => 'Error saving ChatBot!'], 502);
    }

    /**
     * Create new ChatBot.
     *
     * @return \Illuminate\Http\Response
     */
    public function createChatbotAccount(Request $request)
    {
        $request->validate([
            'chatbot_id' => 'required|exists:chat_bots,id',
            'channel_id' => 'required|exists:channels,id',
            'account_id' => 'required|numeric'
        ]);

        //validate if link exists
        $checkIfExists = ChatBotAccountPivot::where([
            'chatbot_id' => $request->chatbot_id,
            'channel_id' => $request->channel_id,
            'account_id' => $request->account_id
        ])->first();

        if ($checkIfExists) {
            return response()->json(['message' => 'Account already linked to chatbot!'], 422);
        }
        //link chatBots
        $chatBotAccount = ChatBotAccountPivot::create([
            'chatbot_id' => $request->chatbot_id,
            'channel_id' => $request->channel_id,
            'account_id' => $request->account_id
        ]);

        if ($chatBotAccount) {

            return response()->json([
                'message' => 'ChatBot linked to account successfully!',
                'chatbot' => $chatBotAccount->chatbot
            ], 200);
        }

        return response()->json(['message' => 'Error linking ChatBot to account!'], 502);
    }

    /**
     * Update ChatBot.
     *
     * @return \Illuminate\Http\Response
     */
    public function updateChatbot(Request $request)
    {
        $request->validate([
            'chatbot_id' => 'required|exists:chat_bots,id',
            'name' => 'required|', new ChatBotUniqueName,
            'description' => 'required'
        ]);

        $chatBot = ChatBot::find($request->chatbot_id);

        if ($chatBot->company_id != Auth::user()->company_id) {
            return response()->json(['message' => 'Not authorized to update this chatbot'], 200);
        }
        $chatBot->update([
            'name' => $request->name,
            'description' => $request->description
        ]);

        return response()->json([
            'message' => 'successfully update',
            'chatbot' => $chatBot
        ], 200);
    }

    /**
     * List all ChatBot.
     *
     * @return \Illuminate\Http\Response
     */
    public function listChatbots()
    {
        $chatBots = ChatBot::where('company_id', Auth::user()->company_id)->get();
        return response()->json([
            'success' => true,
            'chatbot' => $chatBots
        ], 200);
    }

    /**
     * List all ChatBot.
     *
     * @return \Illuminate\Http\Response
     */
    public function getAccounts(Request $request)
    {
        $request->validate([
            'chatbot_id' => 'required|exists:chat_bots,id',
            'channel_id' => 'required|exists:channels,id'
        ]);


        $chatBot = ChatBot::find($request->chatbot_id);

        if ($chatBot->company_id != Auth::user()->company_id) {
            return response()->json(['message' => 'Chatbot does not belong to your company!'], 401);
        }
        $chatBotAccount = ChatBotAccountPivot::where(
            [
                'chatbot_id' =>
                $request->chatbot_id,
                'channel_id' =>
                $request->channel_id
            ]
        )->first();

        if ($chatBotAccount) {
            if ($chatBotAccount->channel_id = 1) {
                $channel = 'WhatsApp';
                $account = WhatsappAccount::find($chatBotAccount->account_id);
                return response()->json([
                    'success' => true,
                    'channel' => $channel,
                    'account' => new ChatBotAccountResource($account)
                ], 200);
            }
            if ($chatBotAccount->channel_id = 2) {
                $channel = 'Facebook';
                $account = FaceBookPage::find($chatBotAccount->account_id);
                return response()->json([
                    'success' => true,
                    'channel' => $channel,
                    'account' => new ChatBotAccountResource($account)
                ], 200);
            }
            if ($chatBotAccount->channel_id = 3) {
                $channel = 'Instagram';
                $account = FaceBookPage::find($chatBotAccount->account_id);
                return response()->json([
                    'success' => true,
                    'channel' => $channel,
                    'account' => new ChatBotAccountResource($account)
                ], 200);
            }
            if ($chatBotAccount->channel_id = 4) {
                $channel = 'Twitter';
                $account = TwitterAccount::find($chatBotAccount->account_id);
                return response()->json([
                    'success' => true,
                    'channel' => $channel,
                    'account' => new ChatBotAccountResource($account)
                ], 200);
            }
        }
    }
    /**
     * List all Accounts.
     *
     * @return \Illuminate\Http\Response
     */
    public function listSocialAccount(Request $request)
    {
        $request->validate([
            'channel_id' => 'required|exists:channels,id'
        ], [
            'channel_id.exists' => 'Channel selected does not exist.'
        ]);

        if ($request->channel_id == 1) {
            $account = WhatsappAccount::where('company_id', Auth::user()->company_id)->get();

            return response()->json([
                'channel_id' => $request->channel_id,
                'account' => WhatsappAccountResource::collection($account)
            ], 200);
        }
        if ($request->channel_id == 2) {
            $account = FaceBookPage::where('company_id', Auth::user()->company_id)->get();

            return response()->json([
                'channel_id' => $request->channel_id,
                'account' => FaceBookAccountResource::collection($account)
            ], 200);
        }

        if ($request->channel_id == 3) {
            $account = InstagramAccount::where('company_id', Auth::user()->company_id)->get();

            return response()->json([
                'channel_id' => $request->channel_id,
                'account' => InstagramAccountResource::collection($account)
            ], 200);
        }

        if ($request->channel_id == 4) {
            $account = TwitterAccount::where('company_id', Auth::user()->company_id)->get();

            return response()->json([
                'channel_id' => $request->channel_id,
                'account' => TwitterAccountResource::collection($account)
            ], 200);
        }

        if ($request->channel_id > 4) {
            return response()->json(['message' => 'Channel not integrated at the moment.'], 200);
        }
    }

    /**
     * Upload ChatBot Files.
     *
     * @return \Illuminate\Http\Response
     */
    public function uploadChatbotFiles(Request $request)
    {
        $company_id = Auth::user()->company_id;
        $error = array();
        $has_error = false;
        foreach ($request->chatbot_files as $key => $chatbot_file) {
            $chatbot_file_request = new Request($chatbot_file);
            $chatbot_file_request->validate([
                "name" => "required|string",
                "file_type" => "required|in:image,document,audio,video",
                "file_url" => "required|url"
            ]);

            $check_duplicate = ChatBotFile::where(["name" => $chatbot_file_request->name, "company_id" => $company_id])->first();
            if (!$check_duplicate) {
                $chatbot_file = ChatBotFile::create([
                    "name" => $chatbot_file_request->name,
                    "file_type" => $chatbot_file_request->file_type,
                    "file_url" => $chatbot_file_request->file_url,
                    "company_id" => $company_id
                ]);
            } else {
                $has_error = true;
                $error[$key]["data"] =  $chatbot_file_request->name;
                $error[$key]["message"] = "A file with the same name exists";
            }
        }
        if ($has_error) {
            return response()->json([
                'message' => 'Error adding file',
                'has_error' => $has_error,
                'error' => $error
            ], 400);
        }
        return response()->json([
            'message' => 'Successfully added file',
            'has_error' => $has_error,
            'error' => $error
        ], 200);
    }

    /**
     * Get ChatBot Files.
     *
     * @return \Illuminate\Http\Response
     */
    public function getChatbotFiles()
    {
        $data = ChatBotFile::where("company_id", Auth::user()->company_id)->get();

        return response()->json($data, 200);
    }

    /**
     * Delete ChatBot Files.
     *
     * @return \Illuminate\Http\Response
     */
    public function deleteChatbotFile(Request $request)
    {
        $request->validate([
            'file_id' => 'required|exists:chat_bot_files,id'
        ]);

        $chatbot_file = ChatBotFile::find($request->file_id);

        if ($chatbot_file->company_id != Auth::user()->company_id) {
            return response()->json(['message' => 'File does not belong to your company!'], 401);
        }

        $chatbot_file->delete();

        return response()->json(['message' => 'File deleted successfully!'], 200);
    }

    /**
     * It validates the request, checks if the components are valid, saves the flow to the database and
     * returns a response
     * 
     * @param Request request The request object.
     * 
     * @return a response with a message that the chatbot flow has been saved successfully.
     */
    public function addChatbotFLow(Request $request)
    {
        $request->validate([
            'chatbot_id' => 'required|exists:chat_bots,id',
            'drawflow' => 'required|array'
        ]);
        $components = array();
        $start_node = array();
        /* Checking if the components are valid. */
        foreach ($request->drawflow["Home"]["data"] as $key => $component) {
            $components[$key]["class"] = $component["class"];
            $components[$key]["name"] = $component["name"];
            $components[$key]["data"] = $component["data"]["data"];
            $components[$key]["inputs"] = ($component["inputs"] == null) ? null : $component["inputs"]["input_1"]["connections"];
            $components[$key]["outputs"] = ($component["outputs"] == null) ? null : $component["outputs"]["output_1"]["connections"];
            if ($components[$key]["class"] == "Start") {
                $start_node = $components[$key];
                $start_node["node"] = $key;

                if (empty($components[$key]["outputs"])) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Start component needs output'
                    ], 400);
                }
            } else if ($components[$key]["class"] == "Stop") {
                if (empty($components[$key]["inputs"])) {

                    return response()->json([
                        'error' => true,
                        'message' => $components[$key]["data"]["name"] . 'Only start component can be without input'
                    ], 400);
                }
            } else {

                if ($components[$key]["class"] == "Attachment") {

                    $this->checkComponent($components[$key]);
                    if ($components[$key]["data"]["attachedfiles"] == null)

                        return response()->json([
                            'error' => true,
                            'message' => $components[$key]["data"]["name"] . "doesn't have an attached file"
                        ], 400);
                } else if ($components[$key]["class"] == "Queue") {
                    $this->checkComponent($components[$key]);

                    if ($components[$key]["data"]["selectedqueue"] == null && $components[$key]["data"]["selectedqueue"] == "") {
                        return response()->json([
                            'error' => true,
                            'message' => $components[$key]["data"]["name"] . "doesn't have queue selected"
                        ], 400);
                    } else {
                        $existing_queue = ChatQueue::find($components[$key]["data"]["selectedqueue"]);
                        if ($existing_queue) {
                            if ($existing_queue->company_id != Auth::user()->company_id) {
                                return response()->json([
                                    'error' => true,
                                    'message' => "Queue selected for " . $components[$key]["data"]["name"] . " does not belong to your company!."
                                ], 400);
                            }
                        } else {
                            return response()->json([
                                'error' => true,
                                'message' => "Queue selected for " . $components[$key]["data"]["name"] . " does not exist!."
                            ], 400);
                        }
                    }
                } else if ($components[$key]["class"] == "SendText") {
                    $this->checkComponent($components[$key]);

                    if ($components[$key]["data"]["output_text"] == null && $components[$key]["data"]["output_text"] == "")

                        return response()->json([
                            'error' => true,
                            'message' => $components[$key]["data"]["name"] . "doesn't have an output text"
                        ], 400);
                } else if ($components[$key]["class"] == "SendTextWait") {
                    $this->checkComponent($components[$key]);

                    if ($components[$key]["data"]["send_text_wait"] == null && $components[$key]["data"]["send_text_wait"] == "")

                        return response()->json([
                            'error' => true,
                            'message' => $components[$key]["data"]["name"] . "doesn't have a defailt text to wait"
                        ], 400);
                }
            }
        }

        /* Checking if the start_node is empty. If it is, it will return an error message. */
        if (empty($start_node)) {
            return response()->json([
                'error' => true,
                'message' => "The ChatBot doesn't have starting point"
            ], 400);
        }

        $this->saveToDb($components, $start_node, $request->chatbot_id, json_encode($request->drawflow));
        return response()->json([
            'message' => 'ChatBot Flow saved successfully!'
        ], 200);
    }

    /**
     * It saves the chatbot flow to the database
     * 
     * @param components The components that are in the flow.
     * @param start_node The start node of the flow.
     * @param chatbot_id The id of the chatbot you want to save the flow for.
     * @param ui_json The JSON string of the UI.
     * 
     * @return the response in json format.
     */
    public function saveToDb($components, $start_node, $chatbot_id, $ui_json)
    {
        try {
            DB::beginTransaction();

            ChatBotUi::where('chatbot_id', $chatbot_id)->delete();

            ChatBotLink::where('chatbot_id', $chatbot_id)->delete();

            $flows = ChatBotFlow::where('chatbot_id', $chatbot_id)->get();

            foreach ($flows as $flow) {
                ChatBotLog::where('chat_flow_id', $flow->id)->delete();

                $flow->delete();
            }

            $chatbot_flow["flow_name"] = $start_node['class'];
            $chatbot_flow["application_type"] = $start_node['class'];
            $chatbot_flow["application_data"] = null;
            $chatbot_flow["ui_node_id"] = $start_node['node'];
            $chatbot_flow["chatbot_id"] = $chatbot_id;
            $chatbot_start = ChatBotFlow::create($chatbot_flow);
            foreach ($components as $key => $component) {
                $chatbot_flow = array();
                if ($component["class"] != 'Start') {
                    if ($component["class"] == 'Stop') {
                        $chatbot_flow["flow_name"]  = $component['class'];
                        $chatbot_flow["application_type"] = $component['class'];
                        $chatbot_flow["ui_node_id"] = $key;
                        $chatbot_flow["chatbot_id"] = $chatbot_id;
                        ChatBotFlow::create($chatbot_flow);
                    } else {
                        $chatbot_flow["flow_name"]  = $components[$key]["data"]['name'];
                        $chatbot_flow["application_type"] = $component['class'];
                        $chatbot_flow["ui_node_id"] = $key;
                        $chatbot_flow["chatbot_id"] = $chatbot_id;
                        if ($component["class"] == "Queue") {
                            $chatbot_flow["application_data"] = $components[$key]["data"]["selectedqueue"];
                        } else if ($component["class"] == "SendText") {
                            $chatbot_flow["application_data"] = strval($components[$key]["data"]["output_text"]);
                        } else if ($component["class"] == "SendTextWait") {
                            $chatbot_flow["application_data"] = strval($components[$key]["data"]["send_text_wait"]);
                        } else if ($component["class"] == "Attachment") {
                            $chatbot_flow["application_data"] = $components[$key]["data"]["attachedfiles"];
                        }
                        ChatBotFlow::create($chatbot_flow);
                    }
                }
            }

            foreach ($components as $key => $component) {
                if ($component["class"] != "Start") {
                    $chatbot_component = ChatBotFlow::where(["ui_node_id" => $key, "chatbot_id" => $chatbot_id])->first();
                    $parent_chatbot_component = ChatBotFlow::where(["ui_node_id" => $component["inputs"][0]["node"], "chatbot_id" => $chatbot_id])->first();
                    ChatBotFlow::where("id", $chatbot_component->id)->update(["parent_id" => $parent_chatbot_component->id]);
                }


                if ($component["class"] == "SendTextWait") {
                    if (!empty($component["outputs"])) {
                        foreach ($component["outputs"] as $out_put) {
                            $next_component = $components[$out_put["node"]];
                            if ($next_component["data"]["configurationprompt"] == null) {
                                return response()->json([
                                    'error' => true,
                                    'message' => $next_component["data"]['name'] . " must has configuration prompt"
                                ], 400);
                            } else {
                                $chatbot_next_component = ChatBotFlow::where(["ui_node_id" => $out_put["node"], "chatbot_id" => $chatbot_id])->first();
                                $chatbot_links["selection"] = $next_component["data"]["configurationprompt"];
                                $chatbot_links["next_flow_id"] = $chatbot_next_component->id;
                                $chatbot_links["chatbot_flow_id"] = $chatbot_component->id;
                                $chatbot_links["chatbot_id"] = $chatbot_id;
                                ChatBotLink::create($chatbot_links);
                            }
                        }
                    } else {
                        return response()->json([
                            'error' => true,
                            'message' => $components[$key]["data"]['name'] . " you can't end flow with a SendTextWait Component"
                        ], 400);
                    }
                }
            }


            $chatbot_ui = ChatBotUi::where("chatbot_id", $chatbot_id)->first();
            if (!$chatbot_ui) {
                ChatBotUi::Create([
                    "chatbot_id" => $chatbot_id,
                    "ui_data" => $ui_json
                ]);
            } else {
                ChatBotUi::where("chatbot_id", $chatbot_id)->update(["ui_data" => $ui_json]);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * It gets the JSON for a chatbot
     * 
     * @param Request request The request object.
     * 
     * @return The chatbot json is being returned.
     */
    public function getChatBotJSON(Request $request)
    {
        $request->validate([
            'chatbot_id' => 'required|exists:chat_bots,id',
        ]);

        $chatBot = ChatBot::find($request->chatbot_id);

        if ($chatBot->company_id != Auth::user()->company_id) {
            return response()->json([
                'message' => 'Not Authorized'
            ], 401);
        }

        $data = ChatBotUi::where('chatbot_id', $request->chatbot_id)->get();

        return response()->json($data, 200);
    }

    /**
     * It checks if the component has a name and if it has inputs.
     * 
     * @param component The component to be checked
     * 
     * @return a response with a json object with the error and message.
     */
    public function checkComponent($component)
    {
        if ($component["data"]["name"] == null || $component["data"]["name"] == "") {
            return response()->json([
                'error' => true,
                'message' => "You have a component without name"
            ], 400);
        }
        if (empty($component["inputs"])) {
            return response()->json([
                'error' => true,
                'message' => $component["data"]["name"] . " Only start component can be without input"
            ], 400);
        }
    }
}
