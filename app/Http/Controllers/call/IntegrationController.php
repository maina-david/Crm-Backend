<?php

namespace App\Http\Controllers\call;

use App\Http\Controllers\Controller;
use App\Http\Resources\CallIntegratoinResource;
use App\Models\CallPopupIntegrationSetting;
use Auth;
use Illuminate\Http\Request;

class IntegrationController extends Controller
{
    public function create_call_integration(Request $request)
    {
        $request->validate([
            "name" => "required|string",
            "url" => "required|string",
            "scope" => "exists:queues,id",
            "type" => "required|in:POPUP,IFRAME"
        ]);

        $duplidate_check = CallPopupIntegrationSetting::where([
            "name" => $request->name,
            "company_id" => Auth::user()->company_id
        ])->first();

        if ($duplidate_check) {
            return response()->json(["You already have another with the same name!"], 422);
        }

        CallPopupIntegrationSetting::create([
            "company_id" => Auth::user()->company_id,
            "name" => $request->name,
            "url" => $request->url,
            "scope" => $request->scope,
            "type" => $request->type
        ]);
        return response()->json(["succefully added!"], 200);
    }

    public function get_call_integrations(Request $request)
    {
        $call_integration = CallPopupIntegrationSetting::where("company_id", Auth::user()->company_id)->get();
        return CallIntegratoinResource::collection($call_integration);
    }

    public function get_call_integration(Request $request)
    {
        $request->validate([
            "call_integration_id" => "required|exists:call_popup_integration_settings,id"
        ]);

        $call_integration = CallPopupIntegrationSetting::find($request->call_integration_id);
        if ($call_integration->company_id != Auth::user()->company_id) {
            return response()->json(["you don't have the right to access this resource!"], 401);
        }
        return new CallIntegratoinResource($call_integration);
    }

    public function update_call_integration(Request $request)
    {
        $request->validate([
            "call_integration_id" => "required|exists:call_popup_integration_settings,id",
            "name" => "required|string",
            "url" => "required|string",
            "scope" => "exists:queues,id",
            "type" => "required|in:POPUP,IFRAME"
        ]);

        $check_duplicate = CallPopupIntegrationSetting::where([
            "name" => $request->name,
            "company_id" => Auth::user()->company_id
        ])->first();

        if ($check_duplicate) {
            if ($check_duplicate->id != $request->call_integration_id) {
                return response()->json(["You have another call integratoin with the same name!"], 422);
            }
        }
        $call_integration_to_update = CallPopupIntegrationSetting::find($request->call_integration_id);
        $call_integration_to_update->update([
            "name" => $request->name,
            "url" => $request->url,
            "scope" => $request->scope,
            "type" => $request->type
        ]);

        return response()->json(["updated successfuly!"], 200);
    }

    public function delete_call_integration(Request $request)
    {
        $request->validate([
            "call_integration_id" => "required|exists:call_popup_integration_settings,id",
        ]);
        $check_access = CallPopupIntegrationSetting::find($request->call_integration_id);
        if ($check_access->company_id != Auth::user()->company_id) {
            return response()->json(["You have another call integratoin with the same name!"], 422);
        }
        $check_access->delete();
        return response()->json(["deleted successfuly!"], 200);
    }
}