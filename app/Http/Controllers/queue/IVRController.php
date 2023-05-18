<?php

namespace App\Http\Controllers\queue;

use App\Http\Controllers\Controller;
use App\Models\IVR;
use App\Models\IVR_ui;
use App\Models\IvrFile;
use App\Models\IVRFlow;
use App\Models\IVRLink;
use App\Services\IVRService;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Constraint\IsEmpty;

use function PHPUnit\Framework\isEmpty;

class IVRController extends Controller
{
    public function create_ivr(Request $request)
    {
        $company_id = $request->user()->company_id;
        $validated_data = $request->validate([
            "name" => "required|string",
            "description" => "required|string"
        ]);

        $ivr_data = (new IVRService())->create_ivr($request->name, $request->description, $company_id);

        return response()->json([
            'message' => 'successfully saved',
            'ivr' => $ivr_data
        ], 200);
    }

    public function update_ivr(Request $request)
    {
        $company_id = $request->user()->company_id;
        $validated_data = $request->validate([
            "id" => "required|exists:ivrs,id",
            "name" => "required|string",
            "description" => "required|string"
        ]);

        $ivr_data = (new IVRService())->update_ivr($request->id, $request->name, $request->description, $company_id);

        return response()->json([
            'message' => 'successfully update',
            'ivr' => $ivr_data
        ], 200);
    }

    public function get_all_ivrs()
    {
        $company_id = (Request())->user()->company_id;
        return IVR::with('dids')->where("company_id", $company_id)->get();
    }

    public function map_ivr_to_did(Request $request)
    {
        $company_id = $request->user()->company_id;
        $validated_data = $request->validate([
            "did_id" => "required|exists:did_lists,id",
            "ivr_id" => "required|exists:ivrs,id"
        ]);
        $did = (new IVRService())->assign_ivr_to_did($request->ivr_id, $request->did_id, $company_id);
        return response()->json([
            'message' => 'successfully added',
            'did' => $did
        ], 200);
    }

    public function delink_ivr_to_did(Request $request)
    {
        $company_id = $request->user()->company_id;
        $validated_data = $request->validate([
            "did_id" => "required|exists:did_lists,id",
        ]);
        $did = (new IVRService())->delink_ivr_to_did($request->did_id, $company_id);
        return response()->json([
            'message' => 'successfully delinked',
            'did' => $did
        ], 200);
    }

    public function upload_ivr_files(Request $request)
    {
        $company_id = $request->user()->company_id;
        $error = array();
        $has_error = false;
        foreach ($request->ivr_files as $key => $ivr_file) {
            $ivr_file_reqiuest = new Request($ivr_file);
            $validated_data = $ivr_file_reqiuest->validate([
                "name" => "required|string",
                "file_url" => "required|url"
            ]);

            $check_duplicate = IvrFile::where(["name" => $ivr_file_reqiuest->name, "company_id" => $company_id])->first();
            if (!$check_duplicate) {
                $ivr_file = IvrFile::create([
                    "name" => $ivr_file_reqiuest->name,
                    "file_url" => $ivr_file_reqiuest->file_url,
                    "company_id" => $company_id
                ]);
            } else {
                $has_error = true;
                $error[$key]["data"] =  $ivr_file_reqiuest->name;
                $error[$key]["message"] = "a file with the same name exists";
            }
        }
        return response()->json([
            'message' => 'successfully added',
            'has_error' => $has_error,
            'error' => $error
        ], 200);
    }

    public function remove_ivr_files(Request $request)
    {
        $request->validate([
            "ivr_file_id" => "required|exists:ivr_files,id"
        ]);

        $file_to_delete = IvrFile::find($request->ivr_file_id);
        if ($file_to_delete->company_id != Auth::user()->company_id) {
            return response()->json("you don't have to access the file", 401);
        }
        ///check if the file is used
        $ivr_flow = IVRFlow::where(["application_data" => $file_to_delete->file_url])->whereIn("application_type", ["PlayBack", "Background"])->first();
        // return $ivr_flow;
        if ($ivr_flow) {
            return response()->json("You can't delete this file, it is in use", 422);
        }
        IvrFile::where("id", $request->ivr_file_id)->delete();
        return response()->json("Successfully removed!", 200);
    }

    public function get_ivr_files()
    {
        $company_id = (Request())->user()->company_id;
        return IvrFile::where("company_id", $company_id)->get();
    }

    public function get_ivr_files_table()
    {
        return IvrFile::where("company_id", Auth::user()->company_id)->paginate();
    }

    public function add_ivr_flow(Request $request)
    {
        // return $request->drawflow["Home"]["data"];
        $components = array();
        $start_node = array();
        $playback = array();
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
                    throw ValidationException::withMessages([" start component needs output"]);
                }
            } else if ($components[$key]["class"] == "Stop") {
                if (empty($components[$key]["inputs"])) {
                    throw ValidationException::withMessages([$component["data"]["name"] . " Only start component can be without input"]);
                }
            } else {
                if ($components[$key]["class"] == "PlayBack" || $components[$key]["class"] == "Background") {
                    $this->check_compnents($components[$key]);
                    if ($components[$key]["data"]["audio_url"] == null)
                        throw ValidationException::withMessages([$components[$key]["data"]["name"] . " doesn't have audio file"]);
                } else if ($components[$key]["class"] == "Queue") {
                    $this->check_compnents($components[$key]);
                    if ($components[$key]["data"]["selectedqueue"] == null && $components[$key]["data"]["selectedqueue"] == "")
                        throw ValidationException::withMessages([$components[$key]["data"]["name"] . " doesn't have queue selected"]);
                } else if ($components[$key]["class"] == "Wait") {
                    $this->check_compnents($components[$key]);
                    if ($components[$key]["data"]["waittime"] == null && $components[$key]["data"]["waittime"] == "")
                        throw ValidationException::withMessages([$components[$key]["data"]["name"] . " doesn't have seconds to wait"]);
                }
            }
        }

        if (empty($start_node)) {
            throw ValidationException::withMessages(["The IVR doesn't have starting point"]);
        }

        $this->save_to_db($components, $start_node, $request->ivr_id, json_encode($request->drawflow));
        return response()->json([
            'message' => 'successfully saved'
        ], 200);
    }

    public function check_compnents($component)
    {
        if ($component["data"]["name"] == null || $component["data"]["name"] == "") {
            throw ValidationException::withMessages([$component["data"]["name"] . " You have a component without name"]);
        }
        if (empty($component["inputs"])) {
            throw ValidationException::withMessages([$component["data"]["name"] . " Only start component can be without input"]);
        }
    }

    public function get_ivr_json(Request $request)
    {
        $company_id = $request->user()->company_id;
        $validate_data = $request->validate([
            "ivr_id" => "required|exists:ivrs,id"
        ]);
        return IVR_ui::where("ivr_id", $request->ivr_id)->first();
    }

    public function save_to_db($components, $start_node, $ivr_id, $ui_json)
    {
        try {
            DB::beginTransaction();
            IVRLink::where("ivr_id", $ivr_id)->delete();
            IVRFlow::where("ivr_id", $ivr_id)->delete();
            $ivr_flow["flow_name"] = $start_node['class'];
            $ivr_flow["application_type"] = $start_node['class'];
            $ivr_flow["application_data"] = null;
            $ivr_flow["ui_node_id"] = $start_node['node'];
            $ivr_flow["ivr_id"] = $ivr_id;
            $ivr_start = IVRFlow::create($ivr_flow);
            foreach ($components as $key => $component) {
                $ivr_flow = array();
                if ($component["class"] != 'Start') {
                    if ($component["class"] == 'Stop') {
                        $ivr_flow["flow_name"]  = $component['class'];
                        $ivr_flow["application_type"] = $component['class'];
                        $ivr_flow["ui_node_id"] = $key;
                        $ivr_flow["ivr_id"] = $ivr_id;
                        $ivr_item = IVRFlow::create($ivr_flow);
                    } else {
                        $ivr_flow["flow_name"]  = $component["data"]['name'];
                        $ivr_flow["application_type"] = $component['class'];
                        $ivr_flow["ui_node_id"] = $key;
                        $ivr_flow["ivr_id"] = $ivr_id;
                        if ($component["class"] == "PlayBack" || $component["class"] == "Background") {
                            $ivr_flow["application_data"] = $component["data"]["audio_url"];
                        } else if ($component["class"] == "Queue") {
                            $ivr_flow["application_data"] = $component["data"]["selectedqueue"];
                        } else if ($component["class"] == "Wait") {
                            $ivr_flow["application_data"] = $component["data"]["waittime"];
                        }
                        $ivr_item = IVRFlow::create($ivr_flow);
                    }
                }
            }

            foreach ($components as $key => $component) {
                if ($component["class"] != "Start") {
                    $ivr_component = IVRFlow::where(["ui_node_id" => $key, "ivr_id" => $ivr_id])->first();
                    $parent_ivr_component = IVRFlow::where(["ui_node_id" => $component["inputs"][0]["node"], "ivr_id" => $ivr_id])->first();
                    IVRFlow::where("id", $ivr_component->id)->update(["parent_id" => $parent_ivr_component->id]);
                }

                if ($component["class"] == "Background") {
                    if (!empty($component["outputs"])) {
                        foreach ($component["outputs"] as $out_put) {
                            $next_component = $components[$out_put["node"]];
                            if ($next_component["data"]["configurationprompt"] == null) {
                                throw ValidationException::withMessages([$next_component["data"]['name'] . " must has configuration prompt"]);
                            } else {
                                $ivr_next_component = IVRFlow::where(["ui_node_id" => $out_put["node"], "ivr_id" => $ivr_id])->first();
                                $ivr_links["selection"] = $next_component["data"]["configurationprompt"];
                                $ivr_links["next_flow_id"] = $ivr_next_component->id;
                                $ivr_links["ivr_flow_id"] = $ivr_component->id;
                                $ivr_links["ivr_id"] = $ivr_id;
                                IVRLink::create($ivr_links);
                            }
                        }
                    } else {
                        throw ValidationException::withMessages([$component["data"]['name'] . " you can't end flow with a background"]);
                    }
                }
            }
            $ivr_ui = IVR_ui::where("ivr_id", $ivr_id)->first();
            if (!$ivr_ui) {
                IVR_ui::Create([
                    "ivr_id" => $ivr_id,
                    "ui_data" => $ui_json
                ]);
            } else {
                IVR_ui::where("ivr_id", $ivr_id)->update(["ui_data" => $ui_json]);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}
