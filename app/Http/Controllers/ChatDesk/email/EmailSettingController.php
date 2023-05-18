<?php

namespace App\Http\Controllers\ChatDesk\email;

use App\Http\Controllers\Controller;
use App\Models\EmailSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmailSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = EmailSetting::all();

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
            'smtp_host' => 'required',
            'smtp_port' => 'required|integer',
            'imap_host' => 'required',
            'imap_port' => 'required|integer',
            'encryption' => 'required|in:tls,ssl',
            'username' => 'required',
            'password' => 'required',
            'timeout' => 'nullable',
            'auth_mode' => 'nullable'
        ]);

        $emailSetting = EmailSetting::create([
            'smtp_host' => $request->smtp_host,
            'smtp_port' => $request->smtp_port,
            'imap_host' => $request->imap_host,
            'imap_port' => $request->imap_port,
            'encryption' => $request->encryption,
            'username' => $request->username,
            'password' => $request->password,
            'timeout' => $request->timeout,
            'auth_mode' => $request->auth_mode
        ]);

        if ($emailSetting) {
            return response()->json(['message' => 'Email setting saved successfully'], 200);
        }

        return response()->json(['message' => 'Error saving Email setting'], 502);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\EmailSetting  $emailSetting
     * @return \Illuminate\Http\Response
     */
    public function show(EmailSetting $emailSetting)
    {
        return response()->json($emailSetting, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\EmailSetting  $emailSetting
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, EmailSetting $emailSetting)
    {
        $request->validate([
            'smtp_host' => 'required',
            'smtp_port' => 'required|integer',
            'imap_host' => 'required',
            'imap_port' => 'required|integer',
            'encryption' => 'required|in:tls,ssl',
            'username' => 'required',
            'password' => 'required',
            'timeout' => 'nullable',
            'auth_mode' => 'nullable'
        ]);

        $emailSetting->update([
            'smtp_host' => $request->smtp_host,
            'smtp_port' => $request->smtp_port,
            'imap_host' => $request->imap_host,
            'imap_port' => $request->imap_port,
            'encryption' => $request->encryption,
            'username' => $request->username,
            'password' => $request->password,
            'timeout' => $request->timeout,
            'auth_mode' => $request->auth_mode
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\EmailSetting  $emailSetting
     * @return \Illuminate\Http\Response
     */
    public function destroy(EmailSetting $emailSetting)
    {
        $emailSetting->delete();

        return response()->json(['message' => 'Email setting deleted successfully!'], 200);
    }
}