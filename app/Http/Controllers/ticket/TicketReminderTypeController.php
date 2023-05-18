<?php

namespace App\Http\Controllers\ticket;

use App\Http\Controllers\Controller;
use App\Http\Resources\TicketReminderTypeResource;
use App\Models\TicketReminderType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketReminderTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data =  TicketReminderType::where('company_id', Auth::user()->company_id)->get();

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
            'name' => 'unique:ticket_reminder_types,name',
            'active' => 'required|boolean'
        ]);

        $ticketremindertype = TicketReminderType::create([
            'company_id' => Auth::user()->company_id,
            'name' => $request->name,
            'active' => $request->active
        ]);

        return new TicketReminderTypeResource($ticketremindertype);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\TicketReminderType  $ticketReminderType
     * @return \Illuminate\Http\Response
     */
    public function show(TicketReminderType $ticketReminderType)
    {
        if ($ticketReminderType->company_id != Auth::user()->company_id) {
            return response()->json(['message' => 'Not authorized!'], 401);
        }

        return new TicketReminderTypeResource($ticketReminderType);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\TicketReminderType  $ticketReminderType
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, TicketReminderType $ticketReminderType)
    {
        $request->validate([
            'name' => 'unique:ticket_reminder_types,name',
            'active' => 'nullable'
        ]);

        if ($ticketReminderType->company_id != Auth::user()->company_id) {
            return response()->json(['message' => 'Not authorized!'], 401);
        }

        $ticketReminderType->update([
            'name' => $request->name,
            'active' => $request->active
        ]);

        return new TicketReminderTypeResource($ticketReminderType);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\TicketReminderType  $ticketReminderType
     * @return \Illuminate\Http\Response
     */
    public function destroy(TicketReminderType $ticketReminderType)
    {
        if ($ticketReminderType->company_id != Auth::user()->company_id) {
            return response()->json(['message' => 'Not authorized!'], 401);
        }

        return response()->json(['message' => 'Ticket reminder deletion is disabled!'], 200);
    }
}