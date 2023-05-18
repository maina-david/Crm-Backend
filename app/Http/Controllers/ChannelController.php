<?php

namespace App\Http\Controllers;

use App\Models\Carrier;
use App\Models\DidList;
use App\Services\ChannelService;
use Auth;
use Illuminate\Http\Request;

class ChannelController extends Controller
{
    public function view_available_dids(Request $request)
    {
        $carriers_available = Carrier::where("country_code", $request->country_code)->pluck("id");

        return DidList::with(["carrier", "ivr"])->whereIn("carrier_id", $carriers_available)->where("allocation_status", "FREE")->get();
    }

    public function view_available_dids_table(Request $request)
    {
        $carriers_available = Carrier::where("country_code", $request->country_code)->pluck("id");
        if ($request->carrier_id) {
            $carriers_available = array($request->carrier_id);
        }

        return DidList::with(["carrier", "ivr"])->whereIn("carrier_id", $carriers_available)->where("allocation_status", "FREE")->paginate();
    }

    public function get_carriers(Request $request)
    {
        $carriers_available = Carrier::where("country_code", $request->country_code)->get();
        return $carriers_available;
    }

    public function get_allocated_dids()
    {
        $request = Request();
        return DidList::with(["carrier", "ivr"])->where(["company_id" => $request->user()->company_id, "allocation_status" => "ALLOCATED"])->get();
    }

    public function get_allocated_dids_table()
    {
        return DidList::with(["carrier", "ivr"])->where(["company_id" => Auth::user()->company_id, "allocation_status" => "ALLOCATED"])->paginate();
    }

    public function assign_phone_number(Request $request)
    {
        $channel_service = new ChannelService();
        $result = $channel_service->assign_phone_number(["id" => $request->did_id, "company_id" => $request->user()->company_id]);
        return response()->json([
            'message' => 'allocated successfuly',
            'did_data' => $result
        ], 200);
    }

    public function remove_phone_number(Request $request)
    {
        $channel_service = new ChannelService();
        $result = $channel_service->remove_phone_number(["id" => $request->did_id, "company_id" => $request->user()->company_id]);
        return response()->json([
            'message' => 'removed successfuly',
            'did_data' => $result
        ], 200);
    }

    public function get_did_with_ivr()
    {
        $company_id = (Request())->user()->company_id;
        return DidList::with('ivr')->where(["company_id" => $company_id])->whereHas("ivr")->get();
    }

    public function get_did_without_ivr()
    {
        $company_id = (Request())->user()->company_id;
        return DidList::with('ivr')->where(["company_id" => $company_id])->whereDoesntHave("ivr")->get();
    }
}