<?php

namespace App\Http\Controllers\Internal\Company;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Internal\Company\Licence;
use App\Models\SmsAccount;
use App\Models\SmsProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CompanyManagementController extends Controller
{
    /**
     * Return a JSON response containing all the companies in the database.
     * 
     * @return A JSON response with all the companies in the database.
     */
    public function listCompanies()
    {
        return response()->json(Company::all(), 200);
    }

    public function activateCompany(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'duration_in_days' => 'required|integer'
        ]);

        if ($request->user()->role != 'ADMIN') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action!'
            ], 401);
        }

        $company = Company::find($request->company_id);

        if ($company->active == FALSE) {
            $licence = Licence::create([
                'company_id' => $request->company_id,
                'issued_on' => Carbon::now(),
                'expires_on' => Carbon::now()->addDays($request->duration_in_days)
            ]);

            if ($licence) {
                $company->users()->update([
                    'status' => 'ACTIVE'
                ]);

                $company->active = TRUE;
                $company->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Company activated successfully!'
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error activating Company!'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Company already active!'
        ], 200);
    }

    /**
     * It deactivates a company
     * 
     * @param Request request The request object.
     * 
     * @return A JSON response with a success message.
     */
    public function deactivateCompany(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id'
        ]);

        if ($request->user()->role != 'ADMIN') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action!'
            ], 401);
        }

        $company = Company::find($request->company_id);
        $company->active = FALSE;
        $company->save();


        $company->users()->update([
            'status' => 'INACTIVE'
        ]);

        $licence = Licence::where([
            'company_id' => $request->company_id
        ])->update(['active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Company deactivated successfully!'
        ], 200);
    }

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
     * It saves a company's SMS account settings
     * 
     * @param Request request The request object.
     * 
     * @return A JSON response with a success message.
     */
    public function saveCompanySMSSetting(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
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
        $SmsAccount->company_id = $request->company_id;
        $SmsAccount->save();

        return response()->json([
            'success' => true,
            'message' => 'Sms Account Saved successfully!'
        ], 200);
    }

    /**
     * It updates the company's SMS account settings
     * 
     * @param Request request The request object.
     * 
     * @return A JSON response with a message.
     */
    public function updateCompanySMSSetting(Request $request)
    {
        $request->validate([
            'sms_account_id' => 'required|exists:sms_accounts,id',
            'provider_id' => 'required|exists:sms_providers,id',
            'name' => 'required',
            'description' => 'nullable',
            'short_code' => 'required',
            'username' => 'required',
            'api_key' => 'nullable'
        ]);

        $SmsAccount = SmsAccount::find($request->sms_account_id);

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
     * It returns all the SMS accounts in the database
     * 
     * @return A collection of all the sms accounts.
     */
    public function smsAccounts()
    {
        $smsAccounts  = SmsAccount::get();

        $smsAccounts->makeVisible(['provider_id', 'api_key', 'api_secret']);

        return response()->json([
            'success' => true,
            'data' => $smsAccounts
        ], 200);
    }
}
