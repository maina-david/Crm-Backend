<?php

namespace App\Http\Controllers\ticket;

use App\Http\Controllers\Controller;
use App\Http\Resources\AccountResource;
use App\Http\Resources\ContactResource;
use App\Http\Resources\EscalationFormResource;
use App\Http\Resources\EscalationHistoryResource;
use App\Models\Ticket;
use App\Http\Resources\TicketDetailResource;
use App\Http\Resources\TicketInteractionResource;
use App\Http\Resources\TicketsResource;
use App\Models\CentralizedForm;
use App\Models\EscalationLevel;
use App\Models\EscalationLog;
use App\Models\EscalationForm;
use App\Models\EscalationFormData;
use App\Models\FormAttribute;
use App\Models\FormAttributeOption;
use App\Models\TicketAssignment;
use App\Models\TicketEntry;
use App\Models\TicketEscalation;
use App\Models\TicketEscationEntry;
use App\Models\TicketInteraction;
use App\Models\TicketNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TicketController extends Controller
{
    /**
     * It returns all the tickets for the company that the user is logged in to.
     * 
     * @return A collection of tickets
     */
    public function index()
    {
        $tickets = Ticket::where('company_id', Auth::user()->company_id)
            ->get();

        return response()->json(['tickets' => TicketsResource::collection($tickets)], 200);
    }


    /**
     * > This function returns all the tickets created by the currently logged in user
     * 
     * @return A collection of tickets that were created by the user.
     */    public function createdTickets()
    {
        $createdTickets = Ticket::where('created_by', Auth::user()->id)->get();

        return response()->json(['tickets' => TicketsResource::collection($createdTickets)], 200);
    }

    /**
     * It returns a JSON response of all the tickets assigned to the currently logged in user
     * 
     * @return A collection of tickets that are assigned to the user.
     */
    public function assignedTickets()
    {
        $assignedTickets = Ticket::where('assigned_to', Auth::user()->id)->get();

        return response()->json(['tickets' => TicketsResource::collection($assignedTickets)], 200);
    }

    /**
     * It returns the ticket details, ticket details and ticket interactions of a ticket
     * 
     * @param Request request The request object
     * 
     * @return The ticket details are being returned.
     */
    public function ticketDetails(Request $request)
    {
        $request->validate([
            'ticket_id' => 'required|exists:tickets,id'
        ]);

        $ticket = Ticket::find($request->ticket_id);

        if ($ticket->company_id != Auth::user()->company_id) {
            return response()->json([
                'error' => true,
                'message' => 'Ticket does not belong to your company!'
            ], 401);
        }

        $escalationLog = EscalationLog::where([
            'ticket_id' => $request->ticket_id,
            'status' => 'OPEN'
        ])->orderBy('id', 'DESC')->limit(1)->first();

        $centralizedForm = null;
        if ($escalationLog) {
            $escalationLevel = EscalationLevel::find($escalationLog->current_level);
            $form_id =  $escalationLevel->form_id;

            $centralizedForm = CentralizedForm::find($form_id);
        }

        $ticketEntry = TicketEntry::where('ticket_entry_id', $ticket->id)->get();

        $ticketInteractions = TicketInteraction::where('ticket_id', $ticket->id)->get();

        return response()->json([
            'user_contact' => $ticket->contact,
            'account' => ($ticket->account_id != null) ? new AccountResource($ticket->account) : null,
            'contact' => ($ticket->contact_id != null) ? new ContactResource($ticket->contacts) : null,
            'escalation_history' => EscalationHistoryResource::collection($ticket->ticket_escations),
            'ticket' => new TicketsResource($ticket),
            'ticket_details' => TicketDetailResource::collection($ticketEntry),
            'ticket_interactions' => TicketInteractionResource::collection($ticketInteractions),
            'escalation_form' => ($centralizedForm != null) ? new EscalationFormResource($centralizedForm) : null
        ], 200);
    }

    /**
     * It creates a new ticket note for a ticket
     * 
     * @param Request request The request object
     * 
     * @return A JSON response with a success message and the ticket note that was just created.
     */
    public function ticketNotes(Request $request)
    {
        $request->validate([
            'ticket_id' => 'required|exists:tickets,id',
            'title' => 'required|max:100',
            'description' => 'required|max:255'
        ]);

        $ticket = Ticket::find($request->ticket_id);

        if ($ticket->company_id != Auth::user()->company_id) {
            return response()->json([
                'error' => true,
                'message' => 'Ticket does not belong to your company!'
            ], 401);
        }

        $ticketNote = TicketNote::create([
            'ticket_id' => $request->ticket_id,
            'user_id' => Auth::user()->id,
            'note_type' => 'TICKET-NOTE',
            'title' => $request->title,
            'description' => $request->description
        ]);

        if ($ticketNote) {
            return response()->json([
                'success' => true,
                'message' => 'Ticket note saved successfully!',
                'ticket_note' => $ticketNote
            ], 200);
        }
    }

    /**
     * It checks if the ticket is open for escalation, if it is, it saves the escalation form data and
     * updates the escalation log
     * 
     * @param Request request This is the request object that contains the form data.
     */
    public function escalate_ticket(Request $request)
    {
        $request->validate([
            'ticket_id' => 'required|exists:tickets,id',
            'form_id' => 'required|exists:centralized_forms,id',
            'form_items' => 'required|array'
        ]);

        $formItems = $request->form_items;
        foreach ($formItems as $key => $formItem) {
            $formItemReq = new Request($formItem);
            $formItemReq->validate([
                'form_item_id' => 'required|integer|exists:form_attributes,id',
                'value' => 'required'
            ]);

            $formAttr = FormAttribute::find($formItemReq->form_item_id);

            if ($formAttr->data_type == "checkbox" || $formAttr->data_type == "radio" || $formAttr->data_type == "Radio" || $formAttr->data_type == "select") {
                $option = FormAttributeOption::find($formItemReq->value);
                if (!$option) {
                    throw ValidationException::withMessages(["This option does not exist!"]);
                }
                if ($option->form_attr_id != $formItemReq->form_item_id) {
                    throw ValidationException::withMessages(["This option does not belong to the form attribute!"]);
                }
            }
        }

        $escalationLog = EscalationLog::where([
            'ticket_id' => $request->ticket_id,
            'status' => 'OPEN'
        ])->first();

        if ($escalationLog) {
            if ($escalationLog->assigned_to != Auth::user()->id) {
                return response()->json([
                    'error' => true,
                    'message' => 'You are not assigned to this ticket!'
                ], 401);
            }

            $escalationLevel = EscalationLevel::find($escalationLog->current_level);

            if ($escalationLevel) {
                $nextLevel = EscalationLevel::where([
                    'sequence' => $escalationLevel->sequence + 1,
                    'company_id' => Auth::user()->company_id
                ])->first();

                if (!$nextLevel) {
                    return response()->json([
                        'error' => true,
                        'message' => 'This is the final escalation level. Please resolve the ticket'
                    ], 422);
                }

                $ticketEscallation = TicketEscalation::where([
                    'ticket_entry_id' => $request->ticket_id,
                    'escalation_point_id' =>  $escalationLevel->escalation_point_id
                ])
                    ->first();

                if ($ticketEscallation) {
                    $ticketEscallation->changed_by = Auth::user()->id;
                    $ticketEscallation->save();
                }

                $escalationForm = EscalationForm::create([
                    'user_id' => Auth::user()->id,
                    'ticket_id' => $request->ticket_id,
                    'form_id' => $request->form_id,
                    'form_items' => json_encode($request->form_items, true)
                ]);

                $ticketEscallation = TicketEscalation::create([
                    "ticket_entry_id" => $request->ticket_id,
                    "escalation_point_id" => $escalationLevel->escalation_point_id,
                    "escalation_level_id" => $nextLevel->id,
                    "changed_by" => Auth::user()->id,
                    "escalation_form_id" => $request->form_id,
                ]);

                foreach ($formItems as $key => $form_item) {
                    EscalationFormData::create([
                        'ticket_id' => $request->ticket_id,
                        'escalation_level_id' => $escalationLog->current_level,
                        'helpdesk_id' => $escalationLevel->helpdesk_id,
                        'escalation_point_id' => $escalationLevel->escalation_point_id,
                        'user_id' => Auth::user()->id,
                        'form_id' => $request->form_id,
                        'form_item_id' => $form_item['form_item_id'],
                        'form_item_value' => $form_item['value']
                    ]);

                    TicketEscationEntry::create([
                        'ticket_escation_id' => $ticketEscallation->id,
                        'escation_form_item_id' => $form_item['form_item_id'],
                        'value' => $form_item['value']
                    ]);
                }

                $ticket = Ticket::find($request->ticket_id);
                $ticket->assigned_to = NULL;
                $ticket->save();

                $escalationLog->changed_by = Auth::user()->id;
                $escalationLog->status = 'ESCALATED';
                $escalationLog->end_time = now();
                $escalationLog->save();

                $ticketAssignment = TicketAssignment::where('user_id', Auth::user()->id)
                    ->where('ticket_id', $request->ticket_id)
                    ->update([
                        'status' => 'ESCALATED',
                        'end_time' => now()
                    ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Escalation saved successfully!'
                ], 200);
            }
        } else {
            return response()->json([
                'error' => true,
                'message' => 'This ticket is not open for escalation!'
            ], 422);
        }
    }

    /**
     * It resolves a ticket
     * 
     * @param Request request The request object.
     */
    public function resolveTicket(Request $request)
    {
        $request->validate([
            'ticket_id' => 'required|exists:tickets,id',
            'form_id' => 'required|exists:centralized_forms,id',
            'form_items' => 'required|array'
        ]);

        $formItems = $request->form_items;
        foreach ($formItems as $key => $formItem) {
            $formItemReq = new Request($formItem);
            $formItemReq->validate([
                'form_item_id' => 'required|integer|exists:form_attributes,id',
                'value' => 'required'
            ]);

            $formAttr = FormAttribute::find($formItemReq->form_item_id);

            if ($formAttr->data_type == "checkbox" || $formAttr->data_type == "radio" || $formAttr->data_type == "Radio" || $formAttr->data_type == "select") {
                $option = FormAttributeOption::find($formItemReq->value);
                if (!$option) {
                    throw ValidationException::withMessages(["This option does not exist!"]);
                }
                if ($option->form_attr_id != $formItemReq->form_item_id) {
                    throw ValidationException::withMessages(["This option does not belong to the form attribute!"]);
                }
            }
        }

        $escalationLog = EscalationLog::where([
            'ticket_id' => $request->ticket_id,
            'status' => 'OPEN'
        ])->first();

        if ($escalationLog) {
            if ($escalationLog->assigned_to != Auth::user()->id) {
                return response()->json([
                    'error' => true,
                    'message' => 'You are not assigned to this ticket!'
                ], 401);
            }

            $escalationForm = EscalationForm::create([
                'user_id' => Auth::user()->id,
                'ticket_id' => $request->ticket_id,
                'form_id' => $request->form_id,
                'form_items' => json_encode($request->form_items, true)
            ]);


            $escalationLevel = EscalationLevel::find($escalationLog->current_level);

            if ($escalationLevel) {
                $ticketEscallation = TicketEscalation::where([
                    'ticket_entry_id' => $request->ticket_id,
                    'escalation_point_id' =>  $escalationLevel->escalation_point_id,
                    'escalation_level_id' =>  $escalationLevel->id,
                ])
                    ->first();

                if ($ticketEscallation) {
                    $ticketEscallation->changed_by = Auth::user()->id;
                    $ticketEscallation->save();
                }

                foreach ($formItems as $key => $form_item) {
                    EscalationFormData::create([
                        'ticket_id' => $request->ticket_id,
                        'escalation_level_id' => $escalationLog->current_level,
                        'helpdesk_id' => $escalationLevel->helpdesk_id,
                        'escalation_point_id' => $escalationLevel->escalation_point_id,
                        'user_id' => Auth::user()->id,
                        'form_id' => $request->form_id,
                        'form_item_id' => $form_item['form_item_id'],
                        'form_item_value' => $form_item['value']
                    ]);

                    TicketEscationEntry::create([
                        'ticket_escation_id' => $ticketEscallation->id,
                        'escation_form_item_id' => $form_item['form_item_id'],
                        'value' => $form_item['value']
                    ]);
                }
                $ticket = Ticket::find($request->ticket_id);

                $ticket->status = 'RESOLVED';

                $ticket->resolved_at = now();

                $ticket->save();

                $escalationLog->changed_by = Auth::user()->id;
                $escalationLog->status = 'RESOLVED';
                $escalationLog->end_time = now();
                $escalationLog->save();

                $ticketAssignment = TicketAssignment::where('user_id', Auth::user()->id)
                    ->where('ticket_id', $request->ticket_id)
                    ->update([
                        'status' => 'RESOLVED',
                        'end_time' => now()
                    ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Ticket resolved successfully!'
                ], 200);
            }
        }
        return response()->json([
            'success' => false,
            'message' => 'This ticket is not open for resolving!'
        ], 422);
    }
}