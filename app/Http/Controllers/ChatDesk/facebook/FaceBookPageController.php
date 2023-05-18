<?php

namespace App\Http\Controllers;

use App\Models\FaceBookPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FaceBookPageController extends Controller
{
    /**
     * This function returns all the Facebook pages that belong to the company that the user is logged in
     * to
     * 
     * @return A collection of all the Facebook pages that belong to the company that the user is logged in
     * to.
     */
    public function index()
    {
        $faceBookPages = FaceBookPage::where('company_id', Auth::user()->company_id)->get();

        return response()->json($faceBookPages, 200);
    }

    /**
     * It validates the request, creates a new Facebook page and returns a JSON response
     * 
     * @param Request request The request object.
     * 
     * @return A JSON response with a success message and the data of the newly created Facebook page.
     */
    public function store(Request $request)
    {
        $request->validate([
            'page_id' => 'required|unique:face_book_pages,page_id',
            'page_name' => 'required',
            'page_description' => 'required',
            'page_access_token' => 'required'
        ]);

        $faceBookPage = FaceBookPage::create([
            'company_id' => Auth::user()->company_id,
            'page_id' => $request->page_id,
            'page_name' => $request->page_name,
            'page_description' => $request->page_description,
            'page_access_token' => $request->page_access_token
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Facebook page saved successfully.',
            'data' => $faceBookPage
        ], 200);
    }

    /**
     * If the Facebook Page does not belong to the company of the user who is logged in, return a 401
     * error. Otherwise, return the Facebook Page
     * 
     * @param FaceBookPage faceBookPage This is the model that we are using to retrieve the data from the
     * database.
     * 
     * @return A JSON object of the Facebook Page.
     */
    public function show(FaceBookPage $faceBookPage)
    {
        if ($faceBookPage->company_id != Auth::user()->company_id) {
            return response()->json('Facebook Page does not belong to your company!', 401);
        }

        return response()->json($faceBookPage, 200);
    }

    /**
     * It updates the Facebook page with the given ID
     * 
     * @param Request request The request object.
     * @param FaceBookPage faceBookPage The FaceBookPage model instance.
     * 
     * @return A JSON response with a success message and the updated Facebook page.
     */
    public function update(Request $request, FaceBookPage $faceBookPage)
    {
        if ($faceBookPage->company_id != Auth::user()->company_id) {
            return response()->json('Facebook Page does not belong to your company!', 401);
        }

        $request->validate([
            'page_id' => 'required|unique:face_book_pages,page_id,' . $faceBookPage->id,
            'page_name' => 'required',
            'page_description' => 'required',
            'page_access_token' => 'required'
        ]);

        $faceBookPage->update([
            'page_id' => $request->page_id,
            'page_name' => $request->page_name,
            'page_description' => $request->page_description,
            'page_access_token' => $request->page_access_token
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Facebook page updated successfully.',
            'data' => $faceBookPage
        ], 200);
    }

    /**
     * It deletes the Facebook page from the database
     * 
     * @param FaceBookPage faceBookPage This is the model that we are using to interact with the database.
     * 
     * @return A JSON response with a success message.
     */
    public function destroy(FaceBookPage $faceBookPage)
    {
        if ($faceBookPage->company_id != Auth::user()->company_id) {
            return response()->json('Facebook Page does not belong to your company!', 401);
        }

        $faceBookPage->delete();

        return response()->json([
            'success' => true,
            'message' => 'Facebook page deleted successfully!'
        ], 200);
    }
}