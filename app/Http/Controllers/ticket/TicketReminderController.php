<?php

namespace App\Http\Controllers\ticket;

use App\Http\Controllers\Controller;
use App\Http\Requests\TicketReminderRequest;
use App\Http\Resources\TicketReminderResource;
use App\Models\TicketReminder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketReminderController extends Controller
{
    /**
     * This function returns all the ticket reminders for the logged in user
     * 
     * @return A collection of all the ticket reminders for the user.
     */
    public function index()
    {
        $ticketreminders = TicketReminder::where('user_id', Auth::user()->id)->get();

        return response()->json($ticketreminders, 200);
    }


    /**
     * It creates a new ticket reminder and returns a ticket reminder resource
     * 
     * @param TicketReminderRequest request The request object.
     * 
     * @return A new TicketReminderResource
     */
    public function store(TicketReminderRequest $request)
    {
        $ticketreminder = TicketReminder::create([
            'ticket_id' => $request->ticket_id,
            'user_id' => Auth::user()->id,
            'reminder_type' => $request->reminder_type,
            'reminder_date' => $request->reminder_date
        ]);

        return new TicketReminderResource($ticketreminder);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\TicketReminder  $ticketReminder
     * @return \Illuminate\Http\Response
     */
    public function show(TicketReminder $ticketReminder)
    {
        return new TicketReminderResource($ticketReminder);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\TicketReminder  $ticketReminder
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, TicketReminder $ticketReminder)
    {
        $ticketReminder->update([
            'reminder_type' => $request->reminder_type,
            'reminder_date' => $request->reminder_date
        ]);

        return new TicketReminderResource($ticketReminder);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\TicketReminder  $ticketReminder
     * @return \Illuminate\Http\Response
     */
    public function destroy(TicketReminder $ticketReminder)
    {
        $ticketReminder->delete();

        return response()->json(['message' => 'Ticket reminder deleted successfully!'], 200);
    }
}