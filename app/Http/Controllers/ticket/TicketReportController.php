<?php

namespace App\Http\Controllers\ticket;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketEscalation;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TicketReportController extends Controller
{
    public function incidentReport(Request $request)
    {
        $request->validate([
            'from' => 'date_format:Y-m-d|required_with:to',
            'to' => 'date_format:Y-m-d|required_with:from',
        ]);
    }
    /**
     * It gets the total number of tickets, the number of resolved tickets and the number of escalated
     * tickets for a given date range
     * 
     * @param Request request This is the request object that is sent to the server.
     * 
     * @return The total number of tickets, the number of resolved tickets, the number of escalated tickets
     * and the resolution rate.
     */
    public function resolutionReport(Request $request)
    {
        $request->validate([
            'from' => 'date_format:Y-m-d|required_with:to',
            'to' => 'date_format:Y-m-d|required_with:from',
        ]);

        /* The above code is getting the total number of tickets, the number of resolved tickets and the number
        of escalated tickets for a given date range. */
        if ($request->has('from') && $request->has('to')) {
            /* Getting the total number of tickets for a given date range. */
            $total_tickets = Ticket::where(
                'company_id',
                Auth::user()->company_id
            )->whereBetween('created_at', [$request->from . " 00:00:00", $request->to . " 23:59:59"])
                ->count();

            /* Getting the number of tickets that are resolved for a given date range. */
            $resolved_tickets = Ticket::where([
                'status' => 'RESOLVED',
                'company_id' => Auth::user()->company_id
            ])->whereBetween('created_at', [$request->from . " 00:00:00", $request->to . " 23:59:59"])
                ->count();

            /* Getting the number of tickets that are escalated for a given date range. */
            $escalated_tickets = Ticket::where([
                'status' => 'ESCALATED',
                'company_id' => Auth::user()->company_id
            ])
                ->whereBetween('created_at', [$request->from . " 00:00:00", $request->to . " 23:59:59"])
                ->count();
        } else {
            /* Getting the total number of tickets for today. */
            $total_tickets = Ticket::whereDate('created_at', Carbon::today()->toDateString())
                ->where('company_id', Auth::user()->company_id)
                ->count();

            /* Getting the number of tickets that are resolved for today. */
            $resolved_tickets = Ticket::whereDate('created_at', Carbon::today()->toDateString())
                ->where([
                    'status' => 'RESOLVED',
                    'company_id' => Auth::user()->company_id
                ])->count();

            /* Getting the number of tickets that are escalated for today. */
            $escalated_tickets = Ticket::whereDate('created_at', Carbon::today()->toDateString())
                ->where([
                    'status' => 'ESCALATED',
                    'company_id' => Auth::user()->company_id
                ])->count();
        }

        /* Checking if the total number of tickets is greater than 0. If it is, it gets the resolution rate. If
        it is not, it sets the resolution rate to 0. */
        $resolution_rate = ($total_tickets > 0) ? ($resolved_tickets / $total_tickets) * 100 : 0;

        /* Returning a JSON response with the total number of tickets, the number of resolved tickets, the
        number of escalated tickets and the resolution rate. */
        return response()->json([
            'total_tickets' => $total_tickets,
            'resolved_tickets' => $resolved_tickets,
            'escalated_tickets' => $escalated_tickets,
            'resolution_rate' => $resolution_rate
        ], 200);
    }

    /**
     * It gets the total number of tickets, the average resolution time, the tickets within SLA and the
     * overdue tickets
     * 
     * @param Request request The request object.
     * 
     * @return a JSON response with the total number of tickets, the average resolution time, the tickets
     *         within SLA and the overdue tickets.
     */
    public function slaReport(Request $request)
    {
        $request->validate([
            'from' => 'date_format:Y-m-d|required_with:to',
            'to' => 'date_format:Y-m-d|required_with:from',
        ]);

        /* Checking if the request has the from and to parameters. If it does, it gets the total number of
            tickets for a given date range. It also gets all the tickets that are within the SLA and the number
            of tickets that are within the SLA. It also gets all the tickets that are outside the SLA and the
            number of tickets that are outside the SLA. It also gets the total number of seconds between the
            resolved_at and created_at of all the tickets that are resolved. If the request does not have the
            from and to parameters, it gets the total number of tickets for today. It also gets all the tickets
            that are within the SLA and the number of tickets that are within the SLA. It also gets all the
            tickets that are outside the SLA and the number of tickets that are outside the SLA. It also gets
            the total number of seconds between the resolved_at and created_at of all the tickets that are
            resolved. 
        */
        if ($request->has('from') && $request->has('to')) {
            /* Getting the total number of tickets for a given date range. */
            $total_tickets = Ticket::where(
                'company_id',
                Auth::user()->company_id
            )
                ->whereBetween('created_at', [$request->from . " 00:00:00", $request->to . " 23:59:59"])
                ->count();

            /* Getting all the tickets that are within the SLA. */
            $tickets_within_sla = TicketEscalation::where('sla_status', 'WITHIN-SLA')
                ->whereBetween('created_at', [$request->from . " 00:00:00", $request->to . " 23:59:59"])
                ->get('ticket_entry_id');

            /* Getting the number of tickets that are within the SLA. */
            $within_sla = Ticket::whereIn('id', $tickets_within_sla)
                ->where('company_id', Auth::user()->company_id)
                ->whereBetween('created_at', [$request->from . " 00:00:00", $request->to . " 23:59:59"])
                ->count();

            /* Getting all the tickets that are outside the SLA. */
            $tickets_outside_sla = TicketEscalation::where('sla_status', 'OUTSIDE-SLA')
                ->whereBetween('created_at', [$request->from . " 00:00:00", $request->to . " 23:59:59"])
                ->get('ticket_entry_id');

            /* Getting the number of tickets that are outside the SLA. */
            $outside_sla = Ticket::whereIn('id', $tickets_outside_sla)
                ->where('company_id', Auth::user()->company_id)
                ->whereBetween('created_at', [$request->from . " 00:00:00", $request->to . " 23:59:59"])
                ->count();

            /* Getting the total number of seconds between the resolved_at and created_at of all the tickets that
                are resolved. 
            */
            $totalNumberOfSeconds = Ticket::where([
                'status' => 'RESOLVED',
                'company_id' => Auth::user()->company_id
            ])->whereBetween('resolved_at', [$request->from . " 00:00:00", $request->to . " 23:59:59"])
                ->get()
                ->map(function ($ticket) {
                    return $ticket->resolved_at->getTimestamp() - $ticket->created_at->getTimestamp();
                })
                ->sum();
        } else {
            /* Getting the total number of tickets for today. */
            $total_tickets = Ticket::where('created_at', Carbon::today()->toDateString())
                ->where('company_id', Auth::user()->company_id)
                ->count();

            /* Getting all the tickets that are within the SLA. */
            $tickets_within_sla = TicketEscalation::where([
                'sla_status' => 'WITHIN-SLA',
                'created_at' => Carbon::today()->toDateString(),
            ])->get('ticket_entry_id');

            /* Getting the number of tickets that are within the SLA. */
            $within_sla = Ticket::whereIn('id', $tickets_within_sla)
                ->where('company_id', Auth::user()->company_id)
                ->whereDate('created_at', Carbon::today())
                ->count();

            /* Getting all the tickets that are outside the SLA. */
            $tickets_outside_sla = TicketEscalation::where([
                'sla_status' => 'OUTSIDE-SLA',
                'created_at' => Carbon::today()->toDateString()
            ])->get('ticket_entry_id');

            /* Getting the number of tickets that are outside the SLA. */
            $outside_sla = Ticket::whereIn('id', $tickets_outside_sla)
                ->where('company_id', Auth::user()->company_id)
                ->whereDate('created_at', Carbon::today())
                ->count();

            /* Getting the total number of seconds between the resolved_at and created_at of all the tickets that
            are resolved. */
            $totalNumberOfSeconds = Ticket::where([
                'resolved_at' => Carbon::today(),
                'status' => 'RESOLVED',
                'company_id' => Auth::user()->company_id
            ])->get()
                ->map(function ($ticket) {
                    return $ticket->resolved_at->getTimestamp() - $ticket->created_at->getTimestamp();
                })
                ->sum();
        }

        /* Getting the average resolution time in minutes. */
        $averageInMinutes = $totalNumberOfSeconds / Ticket::where([
            'status' => 'RESOLVED',
            'company_id' => Auth::user()->company_id
        ])->count();

        /* Converting the average resolution time from minutes to seconds. */
        $average_resolution_time = $averageInMinutes * 60;

        /* Returning a JSON response with the total number of tickets, the average resolution time, the tickets
            within SLA and the overdue tickets. 
        */
        return response()->json([
            'total_tickets' => $total_tickets,
            'average_resolution_time' => $average_resolution_time,
            'tickets_within_sla' => $within_sla,
            'overdue_tickets' => $outside_sla
        ], 200);
    }
}