<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\LogActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ActivityLogController extends Controller
{
    public function get_activity_log(Request $request)
    {
        $from =  Carbon::createFromFormat("Y-m-d", $request->from);
        $to = Carbon::createFromFormat('Y-m-d', $request->to)->addDays(1);
        // return [$from, $to];
        return LogActivity::where("company_id", $request->user()->company_id)->whereBetween('created_at', [$from, $to])->get();
    }
}
