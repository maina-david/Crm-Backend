<?php

namespace App\Http\Controllers\ChatDesk\ChatAccounts;

use App\Http\Controllers\Controller;
use App\Models\FaceBookPage;
use App\Models\InstagramAccount;
use App\Models\SmsAccount;
use App\Models\TwitterAccount;
use App\Models\WhatsappAccount;
use Illuminate\Support\Facades\Auth;

class ChatAccountsController extends Controller
{
    /**
     * It returns all the social media accounts of the company
     * 
     * @return the accounts of the company that is logged in.
     */
    public function index()
    {
        $whatsAppAccount = WhatsappAccount::where('company_id', Auth::user()->company_id)->get();

        $twitterAccount = TwitterAccount::where('company_id', Auth::user()->company_id)->get();

        $instagramAccount = InstagramAccount::where('company_id', Auth::user()->company_id)->get();

        $faceBookAccount = FaceBookPage::where('company_id', Auth::user()->company_id)->get();

        $smsAccount = SmsAccount::where('company_id', Auth::user()->company_id)->get();

        return response()->json([
            'success' => true,
            'whatsapp_accounts' => $whatsAppAccount,
            'twitter_accounts' => $twitterAccount,
            'instagram_accounts' => $instagramAccount,
            'facebook_accounts' => $faceBookAccount,
            'sms_accounts' => $smsAccount
        ], 200);
    }
}
