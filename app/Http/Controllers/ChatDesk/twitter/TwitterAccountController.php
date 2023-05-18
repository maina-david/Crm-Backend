<?php

namespace App\Http\Controllers\ChatDesk\twitter;

use App\Http\Controllers\Controller;
use App\Models\TwitterAccount;
use Auth;
use Illuminate\Http\Request;

class TwitterAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $twitterAccounts = TwitterAccount::where('company_id', Auth::user()->company_id)->get();

        return response()->json($twitterAccounts, 200);
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
            'account_id' => 'required|unique:twitter_accounts,account_id',
            'account_name' => 'required',
            'account_description' => 'required',
            'consumer_key' => 'required',
            'consumer_secret' => 'required',
            'access_token' => 'required',
            'access_token_secret' => 'required'
        ]);

        $twitterAccount = TwitterAccount::create([
            'company_id' => Auth::user()->company_id,
            'account_id' => $request->account_id,
            'account_name' => $request->account_name,
            'account_description' => $request->account_description,
            'consumer_key' => $request->consumer_key,
            'consumer_secret' => $request->consumer_secret,
            'access_token' => $request->access_token,
            'access_token_secret' => $request->access_token_secret
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Twitter account created successfully.',
            'data' => $twitterAccount
        ], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\TwitterAccount  $twitterAccount
     * @return \Illuminate\Http\Response
     */
    public function show(TwitterAccount $twitterAccount)
    {
        if ($twitterAccount->company_id != Auth::user()->company_id) {
            return response()->json(
                [
                    'message' => 'Twitter account does not belong to you company!'
                ],
                401
            );
        }

        return response()->json([
            'success' => true,
            'data' => $twitterAccount
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\TwitterAccount  $twitterAccount
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, TwitterAccount $twitterAccount)
    {
        $request->validate([
            'account_id' => 'required|unique:twitter_accounts,account_id,' . $twitterAccount->id,
            'account_name' => 'required',
            'account_description' => 'required',
            'consumer_key' => 'required',
            'consumer_secret' => 'required',
            'access_token' => 'required',
            'access_token_secret' => 'required'
        ]);

        if ($twitterAccount->company_id != Auth::user()->company_id) {
            return response()->json(
                [
                    'message' => 'Twitter account does not belong to you company!'
                ],
                401
            );
        }
        $twitterAccount->update([
            'account_id' => $request->account_id,
            'account_name' => $request->account_name,
            'account_description' => $request->account_description,
            'consumer_key' => $request->consumer_key,
            'consumer_secret' => $request->consumer_secret,
            'access_token' => $request->access_token,
            'access_token_secret' => $request->access_token_secret
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Twitter account updated successfully.',
            'data' => $twitterAccount
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\TwitterAccount  $twitterAccount
     * @return \Illuminate\Http\Response
     */
    public function destroy(TwitterAccount $twitterAccount)
    {
        if ($twitterAccount->company_id != Auth::user()->company_id) {
            return response()->json(
                [
                    'message' => 'Twitter account does not belong to you company!'
                ],
                401
            );
        }

        $twitterAccount->delete();

        return response()->json([
            'success' => true,
            'message' => 'Twitter account deleted successfully.'
        ], 200);
    }
}
