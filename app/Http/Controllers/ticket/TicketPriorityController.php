<?php

namespace App\Http\Controllers\ticket;

use App\Http\Controllers\Controller;
use App\Http\Resources\TicketPriorityResource;
use App\Models\TicketPriority;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketPriorityController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = TicketPriority::where('company_id', Auth::user()->company_id)->get();

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
            'name' => 'required',
            'description' => 'required',
            'sla' => 'required|numeric'
        ]);

        $ticketPriority = TicketPriority::create([
            'company_id' => Auth::user()->company_id,
            'name' => $request->name,
            'description' => $request->description,
            'sla' => $request->sla
        ]);

        return new TicketPriorityResource($ticketPriority);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\TicketPriority  $ticketPriority
     * @return \Illuminate\Http\Response
     */
    public function show(TicketPriority $ticketPriority)
    {
        if ($ticketPriority->company_id != Auth::user()->company_id) {
            return response()->json(['message' => 'Not authorized!'], 401);
        }
        return new TicketPriorityResource($ticketPriority);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\TicketPriority  $ticketPriority
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, TicketPriority $ticketPriority)
    {
        $request->validate([
            'name' => 'required',
            'description' => 'required',
            'sla' => 'required|numeric'
        ]);

        if ($ticketPriority->company_id != Auth::user()->company_id) {
            return response()->json(['message' => 'Not authorized!'], 401);
        }

        $ticketPriority->update([
            'name' => $request->name,
            'description' => $request->description,
            'sla' => $request->sla
        ]);

        return new TicketPriorityResource($ticketPriority);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\TicketPriority  $ticketPriority
     * @return \Illuminate\Http\Response
     */
    public function destroy(TicketPriority $ticketPriority)
    {
        if ($ticketPriority->company_id != Auth::user()->company_id) {
            return response()->json(['message' => 'Not authorized!'], 401);
        }

        return response()->json(['message' => 'Ticket Priority deletion disabled!'], 200);
    }
}