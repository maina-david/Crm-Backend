<?php

namespace App\Helpers;

use Request;
use App\Models\LogActivity as LogActivityModel;


class LogActivity
{
    public static function addToLog($subject)
    {
        $request = Request();
        $log = [];
        $log['subject'] = $subject;
        $log['url'] = $request->fullUrl();
        $log['method'] = $request->method();
        $log['ip'] = $request->ip();
        // $log['agent'] = $request->header('user-agent');
        $log['user_id'] = auth()->check() ? auth()->user()->id : NULL;
        $log['company_id'] = auth()->check() ? auth()->user()->company_id : NULL;
        $log['email'] = auth()->check() ? auth()->user()->email : NULL;
        LogActivityModel::create($log);
    }


    public static function logActivityLists()
    {
        return LogActivityModel::latest()->get();
    }
}