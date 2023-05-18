<?php

namespace App\Http\Controllers\ticket;

use App\Http\Controllers\Controller;
use App\Http\Resources\EscalationPointResource;
use App\Models\EscalationPoint;
use App\Models\TicketForm;
use App\Models\TicketFormItem;
use App\Models\TicketFormOption;
use Auth;
use Illuminate\Http\Request;

class EscalationPointController extends Controller
{
    /**
     * It returns all the escalation points for the company that the user is logged in to.
     * 
     * @return A list of all escalation points for the company.
     */
    public function index()
    {
        $data = EscalationPoint::withcount('escalation_levels')->where('company_id', Auth::user()->company_id)->get();
        return response()->json(EscalationPointResource::collection($data), 200);
    }


    /**
     * It validates the request, creates a new escalation point, and returns a JSON response
     * 
     * @param Request request The request object.
     * 
     * @return A JSON response with a success message and the data of the escalation point that was
     * created.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'description' => 'required'
        ]);

        $escalationPoint = EscalationPoint::create([
            'company_id' => Auth::user()->company_id,
            'name' => $request->name,
            'description' => $request->description
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Escalation Point created successfully!',
            'data' => $escalationPoint,
        ], 200);
    }


    /**
     * It adds a priority to an escalation point
     * 
     * @param Request request The request object.
     * 
     * @return The response is a JSON object with the following properties:
     * - success: A boolean value indicating whether the request was successful or not.
     * - message: A string containing a message about the request.
     * - data: An object containing the data returned by the request.
     */
    public function addPriority(Request $request)
    {
        $request->validate([
            'priority_id' => 'required|exists:ticket_priorities,id',
            'escalation_point_id' => 'required|exists:escalation_points,id'
        ]);

        $escalationPoint = EscalationPoint::find($request->escalation_point_id);

        if ($escalationPoint->company_id != Auth::user()->company_id) {
            return response()->json(['message' => 'Escalation Point does not belong to your company!'], 401);
        }

        $escalationPoint->update([
            'priority_id' => $request->priority_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Priority added to Escalation Point successfully!',
            'data' => $escalationPoint,
        ], 200);
    }


    /**
     * It adds an escalation matrix to an escalation point
     * 
     * @param Request request The request object.
     * 
     * @return The response is a JSON object with the following properties:
     * - success: A boolean value indicating whether the request was successful or not.
     * - message: A string containing a message about the request.
     * - data: An object containing the updated Escalation Point.
     */
    public function addEscallationMatrix(Request $request)
    {
        $request->validate([
            'escalation_matrix' => 'required|array',
            'ticket_form_id' => 'required|exists:ticket_forms,id',
            'escalation_point_id' => 'required|exists:escalation_points,id'
        ]);

        $json_db = array();
        foreach ($request->escalation_matrix as $key => $matrix) {
            $form_item = TicketFormItem::where(["ticket_form_id" => $request->ticket_form_id, "ui_node_id" => $key])->first();
            if ($form_item) {
                $check_option = TicketFormOption::where(["ticket_form_item_id" => $form_item->id, "option" => $matrix])->first();
                if ($check_option) {
                    $json_db[$form_item->id] = $check_option->id;
                } else {
                    return response()->json(['message' => 'Please check your input!'], 401);
                }
            } else {
                return response()->json(['message' => 'Please check your input!'], 401);
            }
        }

        $escalationPoint = EscalationPoint::find($request->escalation_point_id);

        if ($escalationPoint->company_id != Auth::user()->company_id) {
            return response()->json(['message' => 'Escalation Point does not belong to your company!'], 401);
        }

        $escalationPoint->update([
            'ticket_form_id' => $request->ticket_form_id,
            'escalation_matrix' => $json_db,
            'ui_form' => $request->escalation_matrix
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Escalation Matrix added to Escalation Point successfully!',
            'data' => $escalationPoint,
        ], 200);
    }
    /**
     * It returns the escalation point if it belongs to the user's company
     * 
     * @param EscalationPoint escalationPoint This is the model that we are using to retrieve the data from
     * the database.
     * 
     * @return The escalation point that was requested.
     */
    public function show(EscalationPoint $escalationPoint)
    {
        if ($escalationPoint->company_id != Auth::user()->company_id) {
            return response()->json(['message' => 'Escalation Point does not belong to your company!'], 401);
        }
        return response()->json([
            'success' => true,
            'data' => $escalationPoint,
        ], 200);
    }

    /**
     * It updates the escalation point
     * 
     * @param Request request The request object.
     * @param EscalationPoint escalationPoint The EscalationPoint model instance.
     * 
     * @return A JSON response with a success message and the updated escalation point.
     */
    public function update(Request $request, EscalationPoint $escalationPoint)
    {
        $request->validate([
            'name' => 'required',
            'description' => 'required'
        ]);

        if ($escalationPoint->company_id != Auth::user()->company_id) {
            return response()->json(['message' => 'Escalation Point does not belong to your company!'], 401);
        }

        $escalationPoint->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Escalation Point updated successfully!',
            'data' => $escalationPoint,
        ], 200);
    }

    /**
     * It deletes the escalation point from the database
     * 
     * @param EscalationPoint escalationPoint This is the EscalationPoint model that we are passing to the
     * function.
     * 
     * @return A JSON response with a success message.
     */
    public function destroy(EscalationPoint $escalationPoint)
    {
        if ($escalationPoint->company_id != Auth::user()->company_id) {
            return response()->json(['message' => 'Escalation Point does not belong to your company!'], 401);
        }

        $escalationPoint->delete();

        return response()->json([
            'success' => true,
            'message' => 'Escalation Point deleted successfully!'
        ], 200);
    }
}
