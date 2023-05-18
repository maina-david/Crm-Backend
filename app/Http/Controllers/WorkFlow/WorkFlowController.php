<?php

namespace App\Http\Controllers\WorkFlow;

use App\Http\Controllers\Controller;
use App\Models\WorkFlow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class WorkFlowController extends Controller
{
    public function index()
    {
        return response()->json(WorkFlow::where('company_id', Auth::user()->company_id), 200);
    }

/**
 * It validates the request, checks if the name is already in use, and then creates the work flow
 * 
 * @param Request request This is the request object that contains the data that was sent from the
 * frontend.
 * 
 * @return A JSON response with a success message.
 */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'description' => 'required|max:255'
        ]);

        $nameCheck = WorkFlow::where([
            'name' => $request->name,
            'company_id' => Auth::user()->company_id
        ])->first();

        if ($nameCheck) {
            throw ValidationException::withMessages(['This name is already in use in your company!']);
        }

        $workFlow = WorkFlow::create([
            'company_id' => Auth::user()->company_id,
            'name' => $request->name,
            'description' => $request->description
        ]);

        if ($workFlow) {
            return response()->json([
                'success' => true,
                'message' => 'Work Flow saved successfully!'
            ], 200);
        }
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}