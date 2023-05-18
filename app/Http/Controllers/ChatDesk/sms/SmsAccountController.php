<?php

namespace App\Http\Controllers\ChatDesk\sms;

use App\Http\Controllers\Controller;
use App\Models\SmsAccount;
use App\Models\SmsProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SmsAccountController extends Controller
{
    /**
     * It returns a JSON response of all the SMS providers in the database
     * 
     * @return A JSON response with all the SMS providers.
     */
    public function smsProviders()
    {
        return response()->json(SmsProvider::all(), 200);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(SmsAccount::all(), 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'provider_id' => 'required|exists:sms_providers,id',
            'name' => 'required',
            'description' => 'nullable',
            'short_code' => 'nullable',
            'username' => 'required',
            'api_key' => 'required',
            'api_secret' => 'required_if:provider_id,3'
        ], [
            'api_secret.required_if' => 'API secret is required for Bonga SMS!'
        ]);

        $SmsAccount = new SmsAccount();
        $SmsAccount->provider_id = $request->provider_id;
        $SmsAccount->name = $request->name;
        $SmsAccount->description = $request->description;
        $SmsAccount->short_code = $request->short_code;
        $SmsAccount->username = $request->username;
        $SmsAccount->api_key = $request->api_key;
        if ($request->has('api_secret')) {
            $SmsAccount->api_secret = $request->api_secret;
        }
        $SmsAccount->save();

        return response()->json([
            'success' => true,
            'message' => 'Sms Account Saved successfully!'
        ], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\SmsAccount  $SmsAccount
     * @return \Illuminate\Http\Response
     */
    public function show(SmsAccount $SmsAccount)
    {
        if ($SmsAccount->company_id != Auth::user()->company_id) {
            return response()->json(['message' => 'Not authorized!'], 401);
        }
        return response()->json($SmsAccount, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SmsAccount  $SmsAccount
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SmsAccount $SmsAccount)
    {
        $request->validate([
            'provider_id' => 'required|exists:sms_providers,id',
            'name' => 'required',
            'description' => 'nullable',
            'short_code' => 'required',
            'username' => 'required',
            'api_key' => 'nullable'
        ]);

        $SmsAccount->provider_id = $request->provider_id;
        $SmsAccount->name = $request->name;
        $SmsAccount->description = $request->description;
        $SmsAccount->short_code = $request->short_code;
        $SmsAccount->username = $request->username;
        if ($request->has('api_key')) {
            $SmsAccount->api_key = $request->api_key;
        }
        $SmsAccount->save();

        return response()->json(['message' => 'SMS account updated successfully!'], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\SmsAccount  $SmsAccount
     * @return \Illuminate\Http\Response
     */
    public function destroy(SmsAccount $SmsAccount)
    {
        if ($SmsAccount->company_id != Auth::user()->company_id) {
            return response()->json(['message' => 'Not authorized!'], 401);
        }

        $SmsAccount->delete();

        return response()->json(['message' => 'SMS setting deleted successfully!'], 200);
    }
}