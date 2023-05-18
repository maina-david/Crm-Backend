<?php

namespace App\Http\Controllers\ticket;

use App\Http\Controllers\Controller;
use App\Models\EscalationLevel;
use Auth;
use Illuminate\Http\Request;

class EscalationLevelController extends Controller
{

    /**
     * It returns all the escalation levels for the company that the user is logged in to.
     * 
     * @return A list of all escalation levels for the company.
     */
    public function index(Request $request)
    {
        $request->validate([
            'escalation_point_id' => 'required|exists:escalation_points,id'
        ]);

        $data = EscalationLevel::with('helpdesk', 'form')->where([
            'company_id' => Auth::user()->company_id,
            'escalation_point_id' => $request->escalation_point_id
        ])->get();

        return response()->json($data, 200);
    }


    /**
     * It validates the request, creates a new escalation level and returns a success message with the new
     * escalation level
     * 
     * @param Request request The request object
     * 
     * @return A JSON response with the success, message and data.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'helpdesk_id' => 'required|exists:help_desk_teams,id',
            'form_id' => 'required|exists:centralized_forms,id',
            'escalation_point_id' => 'required|exists:escalation_points,id',
            'sla' => 'required',
            'sla_measurement' => 'required'
        ]);

        $latestEscalationLevel = EscalationLevel::where([
            'company_id' => Auth::user()->company_id
        ])->orderBy('id', 'DESC')->limit(1)->first();

        if ($latestEscalationLevel) {
            $escalationLevel = EscalationLevel::create([
                "name" => $request->name,
                "helpdesk_id"  => $request->helpdesk_id,
                "form_id" => $request->form_id,
                "sequence" => $latestEscalationLevel->sequence + 1,
                "escalation_point_id"  => $request->escalation_point_id,
                "sla"  => $request->sla,
                "sla_measurement" => $request->sla_measurement,
                "company_id" => Auth::user()->company_id,
            ]);
        } else {
            $escalationLevel = EscalationLevel::create([
                "name" => $request->name,
                "helpdesk_id"  => $request->helpdesk_id,
                "form_id" => $request->form_id,
                "sequence" => 1,
                "escalation_point_id"  => $request->escalation_point_id,
                "sla"  => $request->sla,
                "sla_measurement" => $request->sla_measurement,
                "company_id" => Auth::user()->company_id,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Escalation Level created successfully!',
            'data' => $escalationLevel
        ], 200);
    }


    /**
     * It returns the escalation level if it belongs to the user's company
     * 
     * @param EscalationLevel escalationLevel This is the model that we are using to retrieve the data from
     * the database.
     * 
     * @return The escalation level that was requested.
     */
    public function show(EscalationLevel $escalationLevel)
    {
        if ($escalationLevel->company_id != Auth::user()->company_id) {
            return response()->json(['message' => 'Escalation level does not belong to your company!'], 401);
        }

        return response()->json($escalationLevel, 200);
    }


    /**
     * It updates the escalation level with the given id
     * 
     * @param Request request The request object
     * @param EscalationLevel escalationLevel The EscalationLevel model instance.
     * 
     * @return A JSON response with the updated escalation level.
     */
    public function update(Request $request, EscalationLevel $escalationLevel)
    {
        $request->validate([
            'name' => 'required',
            'helpdesk_id' => 'required|exists:help_desk_teams,id',
            'form_id' => 'required|exists:centralized_forms,id',
            'escalation_point_id' => 'required|exists:escalation_points,id',
            'sla' => 'required',
            'sla_measurement' => 'required'
        ]);

        if ($escalationLevel->company_id != Auth::user()->company_id) {
            return response()->json(['message' => 'Escalation level does not belong to your company!'], 401);
        }

        $escalationLevel->update([
            "name" => $request->name,
            "helpdesk_id"  => $request->helpdesk_id,
            "form_id" => $request->form_id,
            "escalation_point_id"  => $request->escalation_point_id,
            "sla"  => $request->sla,
            "sla_measurement" => $request->sla_measurement
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Escalation Level updated successfully!',
            'data' => $escalationLevel
        ], 200);
    }

    /**
     * It deletes the escalation level from the database
     * 
     * @param EscalationLevel escalationLevel This is the model that we are using to interact with the
     * database.
     * 
     * @return A JSON response with a success message.
     */
    public function destroy(EscalationLevel $escalationLevel)
    {
        if ($escalationLevel->company_id != Auth::user()->company_id) {
            return response()->json(['message' => 'Escalation level does not belong to your company!'], 401);
        }

        $escalationLevel->delete();

        return response()->json([
            'success' => true,
            'message' => 'Escalation Level deleted successfully!'
        ], 200);
    }
}