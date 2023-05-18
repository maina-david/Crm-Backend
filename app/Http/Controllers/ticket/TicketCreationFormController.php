<?php

namespace App\Http\Controllers\ticket;

use App\Http\Controllers\Controller;
use App\Http\Resources\TicketCreationFormResource;
use App\Models\TicketCreationForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketCreationFormController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $ticketCreationForm = TicketCreationForm::with('formComponents')
            ->where('company_id', Auth::user()->company_id)
            ->get();

        return new TicketCreationFormResource($ticketCreationForm);
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
            'description' => 'required',
            'account_id' => 'nullable',
            'priority_id' => 'required|exists:ticket_priorities,id'
        ], [
            'priority_id.exists' => 'Selected Ticket priority must exist in the defined priorities'
        ]);

        $ticketCreationForm = TicketCreationForm::create([
            'company_id' => Auth::user()->company_id,
            'account_id' => $request->account_id,
            'name' => $request->name,
            'description' => $request->description,
            'priority_id' => $request->priority_id
        ]);

        if ($ticketCreationForm) {
            return response()->json([
                'success' => true,
                'ticketCreationForm' => new TicketCreationFormResource($ticketCreationForm)
            ], 200);
        }
    }

    /**
     * Store form components.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeFormComponents(Request $request)
    {
        $request->validate([
            'ticket_form_id' => 'required|exists:ticket_creation_forms,id'
        ], [
           'ticket_form_id.exists' => 'Selected ticket creation form does not exist' 
        ]);

        $data = $request->all();

        return $data['child'][0]['child'][0]['child'][0]['child'][0]['child'][0]['child'][0];

    }
    /**
     * Display the specified resource.
     *
     * @param  \App\Models\TicketCreationForm  $ticketCreationForm
     * @return \Illuminate\Http\Response
     */
    public function show(TicketCreationForm $ticketCreationForm)
    {
        if ($ticketCreationForm->company_id != Auth::user()->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to view this Ticket Creation Form'
            ], 401);
        }
        return response()->json([
            'success' => true,
            'ticketCreationForm' => new TicketCreationFormResource($ticketCreationForm)
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\TicketCreationForm  $ticketCreationForm
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, TicketCreationForm $ticketCreationForm)
    {
        $request->validate([
            'name' => 'required',
            'description' => 'required',
            'account_id' => 'nullable',
            'priority_id' => 'required|exists:ticket_priorities,id'
        ], [
            'priority_id.exists' => 'Ticket priority selected must exist in the defined priorities'
        ]);

        if ($ticketCreationForm->company_id != Auth::user()->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to update this Ticket Creation Form'
            ], 401);
        }

        $ticketCreationForm = $ticketCreationForm->update([
            'account_id' => $request->account_id,
            'name' => $request->name,
            'description' => $request->description,
            'priority_id' => $request->priority_id
        ]);

        if ($ticketCreationForm) {
            return response()->json([
                'success' => true,
                'ticketCreationForm' => new TicketCreationFormResource($ticketCreationForm)
            ], 200);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\TicketCreationForm  $ticketCreationForm
     * @return \Illuminate\Http\Response
     */
    public function destroy(TicketCreationForm $ticketCreationForm)
    {
        if ($ticketCreationForm->company_id != Auth::user()->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to delete this Ticket Creation Form'
            ], 401);
        }

        return response()->json([
            'success' => false,
            'message' => 'Deleting Ticket Creation Form is not allowed.'
        ], 502);
    }
}