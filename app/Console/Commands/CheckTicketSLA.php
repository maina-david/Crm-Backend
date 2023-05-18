<?php

namespace App\Console\Commands;

use App\Models\EscalationLevel;
use App\Models\EscalationLog;
use App\Models\Ticket;
use App\Models\TicketAssignment;
use App\Models\TicketEscalation;
use DateInterval;
use DateTime;
use Illuminate\Console\Command;

class CheckTicketSLA extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:ticket_sla';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks the ticket sla and updates escalations';

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
        $ticket_escalations = TicketEscalation::whereNull('changed_by')->get();

        foreach ($ticket_escalations as $key => $ticket_escalation) {
            $escalationLog = EscalationLog::where([
                'ticket_id' => $ticket_escalation->ticket_entry_id,
                'status' => 'OPEN'
            ])->first();

            if ($escalationLog) {
                $escalationLevel = EscalationLevel::find($ticket_escalation->escalation_level_id);
                if ($escalationLevel) {
                    $sla_measurement = $escalationLevel->sla_measurement;

                    $sla_status = '';

                    if ($sla_measurement == 'weeks') {
                        $sla = ($escalationLevel->sla > 1) ? $escalationLevel->sla . ' weeks' : $escalationLevel->sla . ' week';
                        $expected_end = DateTime::createFromFormat('Y-m-d', $escalationLog->created_at)
                            ->add(DateInterval::createFromDateString($sla))
                            ->format('Y-m-d');

                        $sla_status = ($expected_end > DateTime::createFromFormat('Y-m-d', today())) ? "WITHIN-SLA" : "OUTSIDE-SLA";
                    } else if ($sla_measurement == 'days') {
                        $sla = ($escalationLevel->sla > 1) ? $escalationLevel->sla . ' days' : $escalationLevel->sla . ' day';
                        $expected_end = DateTime::createFromFormat('Y-m-d H:i:s', $escalationLog->created_at)
                            ->add(DateInterval::createFromDateString($sla))
                            ->format('Y-m-d H:i:s');

                        $sla_status = ($expected_end > DateTime::createFromFormat('Y-m-d H:i:s', today())) ? "WITHIN-SLA" : "OUTSIDE-SLA";
                    } else if ($sla_measurement == 'hours') {
                        $sla = ($escalationLevel->sla > 1) ? $escalationLevel->sla . ' hours' : $escalationLevel->sla . ' hour';
                        $expected_end = DateTime::createFromFormat('Y-m-d H:i:s', $escalationLog->created_at)
                            ->add(DateInterval::createFromDateString($sla))
                            ->format('Y-m-d H:i:s');

                        $sla_status = ($expected_end > DateTime::createFromFormat('Y-m-d H:i:s', today())) ? "WITHIN-SLA" : "OUTSIDE-SLA";
                    } else if ($sla_measurement == 'minutes') {
                        $sla = ($escalationLevel->sla > 1) ? $escalationLevel->sla . ' minutes' : $escalationLevel->sla . ' minute';
                        $expected_end = DateTime::createFromFormat('Y-m-d H:i:s', $escalationLog->created_at)
                            ->add(DateInterval::createFromDateString($sla))
                            ->format('Y-m-d H:i:s');

                        $sla_status = ($expected_end > DateTime::createFromFormat('Y-m-d H:i:s', today())) ? "WITHIN-SLA" : "OUTSIDE-SLA";
                    } else if ($sla_measurement == 'seconds') {
                        $sla = ($escalationLevel->sla > 1) ? $escalationLevel->sla . ' seconds' : $escalationLevel->sla . ' second';
                        $expected_end = DateTime::createFromFormat('Y-m-d H:i:s', $escalationLog->created_at)
                            ->add(DateInterval::createFromDateString($sla))
                            ->format('Y-m-d H:i:s');

                        $sla_status = ($expected_end > DateTime::createFromFormat('Y-m-d H:i:s', today())) ? "WITHIN-SLA" : "OUTSIDE-SLA";
                    }


                    $TicketAssignment = TicketAssignment::where([
                        'ticket_id' => $ticket_escalation->ticket_entry_id,
                        'status' => 'ASSIGNED'
                    ])->first();

                    $escalation = TicketEscalation::find($ticket_escalation->id);

                    if ($sla_status != '') {

                        if ($TicketAssignment) {
                            $TicketAssignment->sla_status = $sla_status;
                            $TicketAssignment->save();
                        }

                        $escalation->sla_status = $sla_status;
                        $escalation->save();
                    }
                }
            }
        }
    }
}