<?php

namespace App\Http\Controllers;

use App\Events\TestEvent;
use App\Helpers\AdminDashboardHelper;
use Illuminate\Http\Request;

class TestEventController extends Controller
{
    //
    public function testevent()
    {
        ///chhen
        // $data = event(new TestEvent("activities","activity-monitor",["some data"])); // when logs in
        AdminDashboardHelper::call_in_ivr(10);
        return response()->json([
            // 'response' =>  $data
        ], 200);
    }
}
