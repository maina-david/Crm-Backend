<?php

namespace App\Http\Controllers\ticket;

use App\Http\Controllers\Controller;
use App\Http\Resources\AgentSLAReportResource;
use App\Http\Resources\CaseManagement\AgentDataAnalytics;
use App\Http\Resources\HelpDeskDataAnalyticsResource;
use App\Http\Resources\HelpDeskSLAReportResource;
use App\Models\HelpDeskTeam;
use App\Models\Ticket;
use App\Models\TicketEscalation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TicketDashboardController extends Controller
{
    /**
     * It gets the total number of tickets, the number of tickets that are resolved, the resolution rate,
     * the number of tickets that are overdue, and the number of tickets that are within the SLA
     * 
     * @return the total number of tickets, the resolution rate, the number of overdue tickets, and the
     * number of tickets within the SLA.
     */
    public function ticketStatistics()
    {
        $companyID = Auth::user()->company_id;

        $total_tickets = Ticket::where('company_id', $companyID)->count();

        /* Getting the number of tickets that are resolved. */
        $resolved_tickets = Ticket::where([
            'status' => 'RESOLVED',
            'company_id' => $companyID
        ])->count();

        /* Checking if the total number of tickets is greater than 0. If it is, it gets the resolution rate. If
        it is not, it sets the resolution rate to 0. */
        $resolution_rate = ($total_tickets > 0) ? ($resolved_tickets / $total_tickets) * 100 : 0;

        /* Getting all the tickets that are within the SLA. */
        $tickets_within_sla = TicketEscalation::where('sla_status', 'WITHIN-SLA')
            ->get('ticket_entry_id');

        /* Getting the number of tickets that are within the SLA. */
        $within_sla = Ticket::whereIn('id', $tickets_within_sla)
            ->where('company_id', $companyID)
            ->count();

        /* Getting all the tickets that are outside the SLA. */
        $tickets_outside_sla = TicketEscalation::where('sla_status', 'OUTSIDE-SLA')
            ->get('ticket_entry_id');

        /* Getting the number of tickets that are outside the SLA. */
        $outside_sla = Ticket::whereIn('id', $tickets_outside_sla)
            ->where('company_id', $companyID)
            ->count();

        return response()->json([
            'total_tickets' => $total_tickets,
            'resolution_rate' => $resolution_rate,
            'overdue_tickets' => $outside_sla,
            'within_sla' => $within_sla
        ], 200);
    }

    /**
     * It returns a collection of agents with their assigned, escalated and resolved tickets count
     * 
     * @return A collection of agents with their assigned tickets, escalated tickets and resolved tickets.
     */
    public function AgentDataAnalytics()
    {
        $agents = User::whereHas('assigned_tickets')
            ->where('company_id', Auth::user()->company_id)
            ->withCount([
                'assigned_tickets',
                'escalations as escalated_tickets' => function (Builder $query) {
                    $query->where('status', 'ESCALATED');
                },
                'escalations as resolved_tickets' => function (Builder $query) {
                    $query->where('status', 'RESOLVED');
                }
            ])
            ->get();

        return response()->json(AgentDataAnalytics::collection($agents), 200);
    }

    /**
     * It returns a collection of agents with their assigned tickets, total tickets, tickets within SLA and
     * tickets outside SLA
     * 
     * @return A collection of agents with their assigned tickets and the number of tickets within and
     * outside SLA.
     */
    public function AgentSLAReport()
    {
        $agents = User::whereHas('assigned_tickets')
            ->where('company_id', Auth::user()->company_id)
            ->withCount([
                'assigned_tickets as total_tickets',
                'assigned_tickets as tickets_within_sla' => function ($query) {
                    $query->where('sla_status', 'WITHIN-SLA');
                },
                'assigned_tickets as tickets_outside_sla' => function ($query) {
                    $query->where('sla_status', 'OUTSIDE-SLA');
                },
            ])
            ->with('assigned_tickets')
            ->get();

        return response()->json(AgentSLAReportResource::collection($agents), 200);
    }

    /**
     * It gets all the agents who have assigned tickets, and then gets the total number of tickets assigned
     * to them, and the number of resolved tickets assigned to them
     */
    public function AgentResolutionRate()
    {
        $agents = User::whereHas('assigned_tickets')
            ->where('company_id', Auth::user()->company_id)
            ->withCount([
                'assigned_tickets as total_tickets',
                'assigned_tickets as resolved_tickets' => function ($query) {
                    $query->where('status', 'RESOLVED');
                },
            ])
            ->get();

        return $this->formatResolutionRate($agents, 'agents');
    }


    /**
     * It returns a collection of HelpDeskTeam models with their assigned tickets, escalated tickets and
     * resolved tickets counts
     * 
     * @return A collection of HelpDeskDataAnalyticsResource
     */
    public function HelpDeskDataAnalytics()
    {
        $helpdesks = HelpDeskTeam::whereHas('assigned_tickets')
            ->where('company_id', Auth::user()->company_id)
            ->withCount([
                'assigned_tickets',
                'escalations as escalated_tickets' => function (Builder $query) {
                    $query->where('status', 'ESCALATED');
                },
                'escalations as resolved_tickets' => function (Builder $query) {
                    $query->where('status', 'RESOLVED');
                }
            ])
            ->get();

        return response()->json(HelpDeskDataAnalyticsResource::collection($helpdesks), 200);
    }

    /**
     * It returns a collection of HelpDeskTeam models with the total number of assigned tickets, the number
     * of tickets within SLA, and the number of tickets outside SLA
     * 
     * @return A collection of HelpDeskSLAReportResource
     */
    public function HelpDeskSLAReport()
    {
        $helpdesks = HelpDeskTeam::whereHas('assigned_tickets')
            ->where('company_id', Auth::user()->company_id)
            ->withCount([
                'assigned_tickets as total_tickets',
                'assigned_tickets as tickets_within_sla' => function ($query) {
                    $query->where('sla_status', 'WITHIN-SLA');
                },
                'assigned_tickets as tickets_outside_sla' => function ($query) {
                    $query->where('sla_status', 'OUTSIDE-SLA');
                },
            ])
            ->with('assigned_tickets')
            ->get();

        return response()->json(HelpDeskSLAReportResource::collection($helpdesks), 200);
    }

    /**
     * It returns the resolution rate of all the helpdesks in the company.
     */
    public function HelpDeskResolutionRate()
    {
        $helpdesks = HelpDeskTeam::whereHas('assigned_tickets')
            ->where('company_id', Auth::user()->company_id)
            ->withCount([
                'assigned_tickets as total_tickets',
                'assigned_tickets as resolved_tickets' => function ($query) {
                    $query->where('status', 'RESOLVED');
                },
            ])
            ->get();

        return $this->formatResolutionRate($helpdesks, 'helpdesks');
    }

    /**
     * It takes a collection of data, and returns an array of the data in a format that can be used by the
     * charting library
     * 
     * @param collection The collection of data you want to format.
     * @param type The type of data you want to retrieve.
     * 
     * @return an array of the resolution rate for each type of ticket.
     */
    private function formatResolutionRate($collection, $type)
    {
        $response = [];
        for ($i = 0; $i < $collection->count(); $i++) {
            $response[$type][$i] = $collection->pluck('name')[$i];
            $response['resolution_rate'][$i] = ($collection->pluck('resolved_tickets')[$i] / $collection->pluck('total_tickets')[$i]) * 100;
        }

        return $response;
    }
}