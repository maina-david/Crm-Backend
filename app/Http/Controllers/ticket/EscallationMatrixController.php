<?php

namespace App\Http\Controllers\ticket;

use App\Http\Controllers\Controller;
use App\Models\EscallationMatrix;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EscallationMatrixController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $escallationMatrix = EscallationMatrix::where('company_id', Auth::user()->company_id)->get();

        return response()->json($escallationMatrix, 200);
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
            'name' => 'required',
            'description' => 'required'
        ]);

        $escallationMatrix = EscallationMatrix::create([
            'company_id' => Auth::user()->company_id,
            'name' => $request->name,
            'description' => $request->description
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Escallation matrix created successfully!',
            'escallation_matrix' => $escallationMatrix
        ], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\EscallationMatrix  $escallationMatrix
     * @return \Illuminate\Http\Response
     */
    public function show(EscallationMatrix $escallationMatrix)
    {
        if ($escallationMatrix->company_id != Auth::user()->company_id) {
            return response()->json('You are not authorized to view this escallation matrix', 401);
        }

        return response()->json([
            'success' => true,
            'escallation_matrix' => $escallationMatrix
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\EscallationMatrix  $escallationMatrix
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, EscallationMatrix $escallationMatrix)
    {
        $request->validate([
            'name' => 'required',
            'description' => 'required'
        ]);

        $escallationMatrix->update([
            'name' => $request->name,
            'description' => $request->description
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Escallation matrix updated successfully!',
            'escallation_matrix' => $escallationMatrix
        ], 200);
    }

    /**
     * Activate Escallation matrix.
     *
     * @param  \App\Models\EscallationMatrix  $escallationMatrix
     * @return \Illuminate\Http\Response
     */
    public function activateEscallationMatrix($id)
    {
        $escallationMatrix = EscallationMatrix::find($id);

        if ($escallationMatrix->company_id != Auth::user()->company_id) {
            return response()->json('You are not authorized to activate this escallation matrix!', 401);
        }

        if ($escallationMatrix->active == true) {
            return response()->json('Escallation matrix already active!', 422);
        }

        EscallationMatrix::where([
            'company_id' => Auth::user()->company_id
        ])->update([
            'active' => false
        ]);

        $escallationMatrix->active = true;

        $escallationMatrix->save();

        return response()->json('Escallation matrix activated successfully!', 200);
    }

    /**
     * Activate Escallation matrix.
     *
     * @param  \App\Models\EscallationMatrix  $escallationMatrix
     * @return \Illuminate\Http\Response
     */
    public function deactivateEscallationMatrix($id)
    {
        $escallationMatrix = EscallationMatrix::find($id);

        if ($escallationMatrix->company_id != Auth::user()->company_id) {
            return response()->json('You are not authorized to activate this escallation matrix!', 401);
        }

        if ($escallationMatrix->active == false) {
            return response()->json('Escallation matrix already not active!', 422);
        }

        $escallationMatrix->active = false;

        $escallationMatrix->save();

        return response()->json('Escallation matrix deactivated successfully!', 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\EscallationMatrix  $escallationMatrix
     * @return \Illuminate\Http\Response
     */
    public function destroy(EscallationMatrix $escallationMatrix)
    {
        if ($escallationMatrix->company_id != Auth::user()->company_id) {
            return response()->json('You are not authorized to delete this escallation matrix!', 401);
        }

        if ($escallationMatrix->status == true) {
            return response()->json('You can not delete an active escallation matrix!', 422);
        }

        $escallationMatrix->delete();

        return response()->json('Escallation matrix deleted successfully!', 200);
    }
}