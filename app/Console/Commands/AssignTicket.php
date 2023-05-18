<?php

namespace App\Console\Commands;

use App\Events\AssignedTicketEvent;
use App\Models\EscalationLevel;
use App\Models\EscalationLog;
use App\Models\HelpDeskTeam;
use App\Models\HelpDeskTeamUsers;
use App\Models\Ticket;
use App\Models\TicketAssignment;
use App\Models\TicketEscalation;
use App\Models\User;
use Illuminate\Console\Command;

class AssignTicket extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assign:tickets';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assigns tickets to helpdesk team users.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $tickets = Ticket::where("status", "ESCALATED")->whereNull('assigned_to')->get();

        if ($tickets->count() > 0) {
            foreach ($tickets as $ticket) {
                $ticket_escalation = TicketEscalation::where("ticket_entry_id", $ticket->id)->latest()->first();
                if ($ticket_escalation) {
                    $escalation_level = EscalationLevel::find($ticket_escalation->escalation_level_id);
                    if ($escalation_level) {
                        $helpdeskteam = HelpDeskTeam::find($escalation_level->helpdesk_id);
                        if ($helpdeskteam) {
                            $agent = HelpDeskTeamUsers::where([
                                'help_desk_team_id' => $helpdeskteam->id,
                                'active' => true
                            ])->inRandomOrder()->first();

                            if ($agent) {
                                $assignTicket = TicketAssignment::create([
                                    'ticket_id' => $ticket->id,
                                    'user_id' => $agent->user_id,
                                    'status' => 'ASSIGNED',
                                    'start_time' => now()
                                ]);

                                if ($assignTicket) {

                                    $ticketEscalation = TicketEscalation::where('ticket_entry_id', $ticket->id)->first();

                                    if ($ticketEscalation) {
                                        $checkEscalationLog = EscalationLog::where([
                                            'ticket_id' => $ticket->id,
                                            'status' => 'ESCALATED'
                                        ])->orderBy('id', 'DESC')->first();

                                        if ($checkEscalationLog) {

                                            $currentLevel = EscalationLevel::find($checkEscalationLog->current_level);

                                            $nextLevelID = EscalationLevel::where([
                                                'sequence' => $currentLevel->sequence + 1,
                                                'company_id' => $ticket->company_id
                                            ])->min('id');

                                            if ($nextLevelID) {

                                                $assign = Ticket::find($ticket->id);
                                                $assign->assigned_to = $agent->user_id;
                                                $assign->save();

                                                EscalationLog::create([
                                                    'ticket_id' => $ticket->id,
                                                    'previous_level' => $checkEscalationLog->current_level,
                                                    'current_level' => $nextLevelID,
                                                    'escalation_point_id' => $ticketEscalation->escalation_point_id,
                                                    'assigned_to' => $agent->user_id,
                                                    'start_time' => now(),
                                                    'status' => 'OPEN'
                                                ]);
                                            } else {
                                                $this->error('Ticket on last escalation step!');
                                            }
                                        } else {

                                            $assign = Ticket::find($ticket->id);
                                            $assign->assigned_to = $agent->user_id;
                                            $assign->save();

                                            EscalationLog::create([
                                                'ticket_id' => $ticket->id,
                                                'previous_level' => 0,
                                                'current_level' => $ticketEscalation->escalation_level_id,
                                                'escalation_point_id' => $ticketEscalation->escalation_point_id,
                                                'assigned_to' => $agent->user_id,
                                                'start_time' => now(),
                                                'status' => 'OPEN'
                                            ]);
                                        }
                                    }

                                    $user = User::find($agent->user_id);

                                    AssignedTicketEvent::dispatch($user, $ticket);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}