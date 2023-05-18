<?php

namespace App\Http\Controllers\ChatDesk\whatsapp;

use App\Http\Controllers\Controller;
use App\Http\Resources\WhatsappAccountResource;
use App\Models\WhatsappAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WhatsappAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = WhatsappAccount::where('company_id', Auth::user()->company_id)->get();

        return response()->json($data, 200);
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
            'account_name' => 'required',
            'description' => 'required',
            'phone_number' => 'required|numeric|min:12|unique:whatsapp_accounts,phone_number',
            'phone_number_id' => 'required|unique:whatsapp_accounts,phone_number_id',
            'active' => 'required|boolean'
        ]);

        $whatsappAccount = WhatsappAccount::create([
            'company_id' => Auth::user()->company_id,
            'account_name' => $request->account_name,
            'description' => $request->description,
            'phone_number' => $request->phone_number,
            'phone_number_id' => $request->phone_number_id,
            'active' => $request->active
        ]);

        if ($whatsappAccount) {
            return response()->json(['message' => 'WhatsApp Account saved successfully!', 200]);
        }

        return response()->json(['message' => 'Error saving WhatsApp Account!', 502]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\WhatsappAccount  $whatsappAccount
     * @return \Illuminate\Http\Response
     */
    public function show(WhatsappAccount $whatsappAccount)
    {
        if ($whatsappAccount->company_id != Auth::user()->company_id) {
            return response()->json(['message' => 'This WhatsApp account does not belong to you companyr!'], 401);
        }

        return new WhatsappAccountResource($whatsappAccount);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\WhatsappAccount  $whatsappAccount
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, WhatsappAccount $whatsappAccount)
    {
        $request->validate([
            'account_name' => 'required',
            'description' => 'required',
            'phone_number' => 'required|numeric|min:12|starts_with:254|unique:whatsapp_accounts,phone_number,' . $whatsappAccount->id,
            'phone_number_id' => 'required|unique:whatsapp_accounts,phone_number_id,' . $whatsappAccount->id,
            'active' => 'required|boolean'
        ]);

        if ($whatsappAccount->company_id != Auth::user()->company_id) {
            return response()->json(['message' => 'Not authorized!'], 401);
        }

        $whatsappAccount->update([
            'account_name' => $request->account_name,
            'description' => $request->description,
            'phone_number' => $request->phone_number,
            'phone_number_id' => $request->phone_number_id,
            'active' => $request->active
        ]);

        return response()->json(['message' => 'Whatsapp account updated successfully!'], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\WhatsappAccount  $whatsappAccount
     * @return \Illuminate\Http\Response
     */
    public function destroy(WhatsappAccount $whatsappAccount)
    {
        if ($whatsappAccount->company_id != Auth::user()->company_id) {
            return response()->json(['message' => 'Not authorized!'], 401);
        }

        return response()->json(['message' => 'Whatsapp account deletion is not allowed!'], 401);
    }
}
