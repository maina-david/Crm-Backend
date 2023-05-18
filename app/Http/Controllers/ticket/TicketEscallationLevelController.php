<?php

namespace App\Http\Controllers\ticket;

use App\Http\Controllers\Controller;
use App\Models\TicketEscallationLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketEscallationLevelController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $levels = TicketEscallationLevel::where('company_id', Auth::user()->company_id)->get();

        return response()->json($levels, 200);
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
            'name' => 'required|unique:ticket_escallation_levels,name',
            'active' => 'required|boolean'
        ]);

        $level = TicketEscallationLevel::create([
            'company_id' => Auth::user()->company_id,
            'name' => $request->name,
            'active' => $request->active
        ]);

        if ($level) {
            return response()->json(['message' => 'Escallation level saved successfully!'], 200);
        }

        return response()->json(['message' => 'Error saving Escallation level!'], 502);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\TicketEscallationLevel  $ticketEscallationLevel
     * @return \Illuminate\Http\Response
     */
    public function show(TicketEscallationLevel $ticketEscallationLevel)
    {
        if ($ticketEscallationLevel->company_id != Auth::user()->company_id) {
            return response()->json(['message' => 'Not authorized!'], 401);
        }
        return response()->json($ticketEscallationLevel, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\TicketEscallationLevel  $ticketEscallationLevel
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, TicketEscallationLevel $ticketEscallationLevel)
    {
        $request->validate([
            'name' => 'required|unique:ticket_escallation_levels,name,' . $ticketEscallationLevel->id,
            'active' => 'required|boolean'
        ]);

        if ($ticketEscallationLevel->company_id != Auth::user()->company_id) {
            return response()->json(['message' => 'Not authorized!'], 401);
        }

        $ticketEscallationLevel->name = $request->name;
        $ticketEscallationLevel->active = $request->active;
        $ticketEscallationLevel->save();

        return response()->json(['message' => 'Escallation level updated successfully!'], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\TicketEscallationLevel  $ticketEscallationLevel
     * @return \Illuminate\Http\Response
     */
    public function destroy(TicketEscallationLevel $ticketEscallationLevel)
    {
        if ($ticketEscallationLevel->company_id != Auth::user()->company_id) {
            return response()->json(['message' => 'Not authorized!'], 401);
        }

        $ticketEscallationLevel->delete();

        return response()->json(['message' => 'Escallation level deeted successfully!'], 200);
    }
}