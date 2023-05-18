<?php

namespace App\Http\Controllers\ChatDesk\instagram;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FaceBookPage;
use App\Models\InstagramAccount;
use Auth;

class InstagramAccountController extends Controller
{

    /**
     * It returns all the instagram accounts for the company that the user is logged in to.
     * 
     * @return An array of InstagramAccounts
     */
    public function index()
    {
        $instagramAccounts = InstagramAccount::where('company_id', Auth::user()->company_id)->get();

        return response()->json($instagramAccounts, 200);
    }

    /**
     * It creates a new instagram account
     * 
     * @param Request request The request object.
     * 
     * @return A JSON response with the success message and the data of the newly created instagram
     * account.
     */
    public function store(Request $request)
    {
        $request->validate([
            'facebook_page_id' => 'required|exists:face_book_pages,id',
            'account_id' => 'required|unique:instagram_accounts,account_id',
            'account_name' => 'required',
            'account_description' => 'required'
        ]);

        $facebookPage = FaceBookPage::find($request->facebook_page_id);

        if ($facebookPage->company_id != Auth::user()->company_id) {
            return response()->json(
                [
                    'message' => 'You are trying to link a facebook page that does not belong to you company!'
                ],
                401
            );
        }

        $instagramAccount = InstagramAccount::create([
            'company_id' => Auth::user()->company_id,
            'facebook_page_id' => $request->facebook_page_id,
            'account_id' => $request->account_id,
            'account_name' => $request->account_name,
            'account_description' => $request->account_description
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Instagram account created successfully.',
            'data' => $instagramAccount
        ], 200);
    }

    /**
     * If the Instagram account does not belong to the user's company, return a 401 error. Otherwise,
     * return the Instagram account
     * 
     * @param InstagramAccount instagramAccount This is the model that we are using to retrieve the data
     * from the database.
     * 
     * @return The Instagram account that belongs to the company that is logged in.
     */
    public function show(InstagramAccount $instagramAccount)
    {
        if ($instagramAccount->company_id != Auth::user()->company_id) {
            return response()->json(
                [
                    'message' => 'Instagram account does not belong to you company!'
                ],
                401
            );
        }

        return response()->json([
            'success' => true,
            'data' => $instagramAccount
        ], 200);
    }

    /**
     * It updates an instagram account
     * 
     * @param Request request The request object.
     * @param InstagramAccount instagramAccount The InstagramAccount model instance that we want to update.
     * 
     * @return The updated instagram account.
     */
    public function update(Request $request, InstagramAccount $instagramAccount)
    {
        $request->validate([
            'facebook_page_id' => 'required|exists:face_book_pages,id',
            'account_id' => 'required|unique:instagram_accounts,account_id,' . $instagramAccount->id,
            'account_name' => 'required',
            'account_description' => 'required'
        ]);

        $facebookPage = FaceBookPage::find($request->facebook_page_id);

        if ($facebookPage->company_id != Auth::user()->company_id) {
            return response()->json(
                [
                    'message' => 'You are trying to link a facebook page that does not belong to you company!'
                ],
                401
            );
        }
        if ($instagramAccount->company_id != Auth::user()->company_id) {
            return response()->json(
                [
                    'message' => 'Instagram account does not belong to you company!'
                ],
                401
            );
        }

        $instagramAccount->update([
            'facebook_page_id' => $request->facebook_page_id,
            'account_id' => $request->account_id,
            'account_name' => $request->account_name,
            'account_description' => $request->account_description
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Instagram account updated successfully.',
            'data' => $instagramAccount
        ], 200);
    }

    /**
     * It deletes the Instagram account from the database
     * 
     * @param InstagramAccount instagramAccount This is the model that we are using to interact with the
     * database.
     * 
     * @return A JSON response with a success message.
     */
    public function destroy(InstagramAccount $instagramAccount)
    {
        if ($instagramAccount->company_id != Auth::user()->company_id) {
            return response()->json(
                [
                    'message' => 'Instagram account does not belong to you company!'
                ],
                401
            );
        }

        $instagramAccount->delete();

        return response()->json([
            'success' => true,
            'message' => 'Instagram account deleted successfully.'
        ], 200);
    }
}