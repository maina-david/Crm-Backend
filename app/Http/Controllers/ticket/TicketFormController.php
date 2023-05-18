<?php

namespace App\Http\Controllers\ticket;

use App\Http\Controllers\Controller;
use App\Models\EscalationLevel;
use App\Models\EscalationPoint;
use App\Models\InteractionReview;
use App\Models\PendingTicket;
use App\Models\Ticket;
use App\Models\TicketChannel;
use App\Models\TicketEntry;
use App\Models\TicketEscalation;
use App\Models\TicketForm;
use App\Models\TicketFormItem;
use App\Models\TicketFormOption;
use App\Models\TicketFormRelationship;
use App\Models\TicketFormUIJson;
use App\Models\TicketInteraction;
use App\Services\PhoneFormatterService;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Log;

class TicketFormController extends Controller
{
    /**
     * It creates a new ticket form
     * 
     * @param Request request The request object.
     * 
     * @return A JSON response with a message.
     */
    public function create_ticket_form(Request $request)
    {
        $request->validate([
            "name" => "required|string",
            "description" => "required|string"
        ]);
        $company_id = $request->user()->company_id;
        $check_duplicate = TicketForm::where(["name" => $request->name, "company_id" => $company_id])->first();

        if ($check_duplicate) {
            throw ValidationException::withMessages(["Duplicate form name"]);
        }

        TicketForm::create([
            "name" => $request->name,
            "description" => $request->description,
            "company_id" => $company_id
        ]);

        return response()->json([
            'message' => 'Successfully added'
        ], 200);
    }

    /**
     * It activates a ticket form
     * 
     * @param Request request The request object.
     * 
     * @return A JSON response with a success message.
     */
    public function activateTicketForm(Request $request)
    {
        $request->validate([
            'ticket_form_id' => 'required|exists:ticket_forms,id'
        ]);

        $ticketForm = TicketForm::find($request->ticket_form_id);

        if ($ticketForm->company_id != Auth::user()->company_id) {
            return response()->json('Unauthorized', 401);
        }

        $checkActive = TicketForm::where(['company_id' => Auth::user()->company_id, 'active' => true])->first();

        if ($checkActive) {
            return response()->json('Deactivate the other ticket forms first!', 422);
        }

        $ticketForm->active = true;

        $ticketForm->save();

        return response()->json([
            'success' => true,
            'message' => 'Ticket form activated successfully!'
        ], 200);
    }

    /**
     * It returns the active ticket form for the company
     * 
     * @return A ticket form
     */
    public function activeTicketForm()
    {

        $ticketForm = TicketForm::where([
            'company_id' => Auth::user()->company_id,
            'active' => true
        ])->first();

        if ($ticketForm) {
            return response()->json([
                'success' => true,
                'ticket_form' => $ticketForm
            ], 200);
        }

        return response()->json([
            'success' => true,
            'message' => 'No active ticket form'
        ], 200);
    }

    /**
     * It deactivates a ticket form
     * 
     * @param Request request The request object
     * 
     * @return A JSON response with a success message.
     */
    public function deactivateTicketForm(Request $request)
    {
        $request->validate([
            'ticket_form_id' => 'required|exists:ticket_forms,id'
        ]);

        $ticketForm = TicketForm::find($request->ticket_form_id);

        if ($ticketForm->company_id != Auth::user()->company_id) {
            return response()->json('Unauthorized', 401);
        }


        $ticketForm->active = false;

        $ticketForm->save();

        return response()->json([
            'success' => true,
            'message' => 'Ticket form deactivated successfully!'
        ], 200);
    }

    /**
     * It updates the ticket form
     * 
     * @param Request request The request object.
     * 
     * @return A JSON response with a message of "Successfully updated"
     */
    public function update_ticket_form(Request $request)
    {
        $request->validate([
            "ticket_form_id" => "required|exists:ticket_forms,id",
            "name" => "required|string",
            "description" => "required|string"
        ]);

        $company_id = $request->user()->company_id;
        $check_duplicate = TicketForm::where(["name" => $request->name, "company_id" => $company_id])->first();

        if ($check_duplicate) {
            if ($check_duplicate->id != $request->ticket_form_id)
                throw ValidationException::withMessages(["Duplicate form name"]);
        }

        $update_ticket_form = TicketForm::find($request->ticket_form_id);

        $update_ticket_form->name = $request->name;
        $update_ticket_form->description = $request->description;
        $update_ticket_form->save();

        return response()->json([
            'message' => 'Successfully updated'
        ], 200);
    }

    /**
     * It returns all the ticket forms for the company that the user belongs to
     * 
     * @param Request request This is the request object that contains the user object.
     * 
     * @return The ticket form for the company.
     */
    public function get_ticket_form(Request $request)
    {
        $company_id = $request->user()->company_id;
        return TicketForm::where("company_id", $company_id)->get();
    }

    /**
     * It validates the request, checks if the components are valid, and saves the components to the
     * database
     * 
     * @param Request request The request object.
     */
    public function add_items_to_ticket_form(Request $request)
    {
        $request->validate([
            "ticket_form_id" => "required|exists:ticket_forms,id",
            "drawflow" => "required|array",
        ]);
        logger($request->drawflow);
        foreach ($request->drawflow["Home"]["data"] as $key => $component) {
            $components[$key]["class"] = $component["class"];
            $components[$key]["name"] = $component["name"];
            $components[$key]["data"] = $component["data"]["data"];
            $components[$key]["ui_node_id"] = $component["id"];
            $components[$key]["inputs"] = ($component["inputs"] == null) ? null : $component["inputs"]["input_1"]["connections"];
            $components[$key]["outputs"] = ($component["outputs"] == null) ? null : $component["outputs"]["output_1"]["connections"];
            if ($components[$key]["class"] == "Start") {
                $start_node = $components[$key];
                $start_node["node"] = $key;

                if (empty($components[$key]["outputs"])) {
                    throw ValidationException::withMessages([" start component needs output"]);
                }
            } else if ($components[$key]["class"] == "Stop") {
                if (empty($components[$key]["inputs"])) {
                    throw ValidationException::withMessages([$component["data"]["name"] . " Only start component can be without input"]);
                }
            } else {
                if ($components[$key]["class"] == "Radio" || $components[$key]["class"] == "Dropdown" || $components[$key]["class"] == "Checkbox") {
                    if ($components[$key]["data"]["options"] == null)
                        throw ValidationException::withMessages([$components[$key]["data"]["name"] . " doesn't have audio file"]);
                    else
                        $components[$key]["options"] = $components[$key]["data"]["options"];
                } else if ($components[$key]["class"] == "Checkbox") {
                    if ($components[$key]["data"]["options"] == null)
                        throw ValidationException::withMessages([$components[$key]["data"]["name"] . " doesn't have audio file"]);
                    else
                        $components[$key]["options"] = $components[$key]["data"]["options"];
                }

                if ($components[$key]["class"] != "Start" && $components[$key]["class"] != "Stop") {
                    $components[$key]["label"] = $components[$key]["data"]["label"];
                    $components[$key]["place_holder"] = (array_key_exists("placeholder", $components[$key]["data"])) ? $components[$key]["data"]["placeholder"] : "";
                    $this->check_compnents($components[$key]);
                }
            }
        }
        if (empty($start_node)) {
            throw ValidationException::withMessages(["The form doesn't have starting point"]);
        }

        $this->save_to_db($components, $start_node, $request->ticket_form_id, json_encode($request->drawflow));
        return response()->json([
            'message' => 'successfully saved'
        ], 200);
    }

    /**
     * It checks if the component has a label and placeholder.
     * 
     * @param component The component object
     * 
     * @return the component.
     */
    public function check_compnents($component)
    {
        return $component;
        if ($component["data"]["name"] == null || $component["data"]["data"]["label"] == "") {
            throw ValidationException::withMessages([$component["data"]["name"] . " You have a component without lable"]);
        }
        if ($component["data"]["name"] == null || $component["data"]["data"]["placeholder"] == "") {
            throw ValidationException::withMessages([$component["data"]["name"] . " You have a component without placeholder"]);
        }
        if (empty($component["inputs"])) {
            throw ValidationException::withMessages([$component["data"]["name"] . " Only start component can be without input"]);
        }
    }

    /**
     * It saves the form data to the database
     * 
     * @param components This is the array of components that you get from the UI.
     * @param start_node The start node of the flow.
     * @param ticket_form_id The id of the ticket form you want to save the components to.
     * @param ui_json The json string of the UI
     */
    public function save_to_db($components, $start_node, $ticket_form_id, $ui_json)
    {
        // return $components;
        try {
            DB::beginTransaction();

            $ticket_form_item["ticket_form_id"] = $ticket_form_id;
            $ticket_form_item["data_type"] = $start_node['class'];
            $ticket_form_item["sequence"] = 0;
            $ticket_form_item["ui_node_id"] = $start_node['ui_node_id'];
            TicketFormItem::create($ticket_form_item);
            foreach ($components as $key => $component) {
                $ticket_form_item = array();
                if ($component["class"] != 'Start') {
                    if ($component["class"] == 'Stop') {
                        $ticket_form_item["ticket_form_id"] = $ticket_form_id;
                        $ticket_form_item["data_type"] = $component['class'];
                        $ticket_form_item["sequence"] = $key + 1;
                        $ticket_form_item["ui_node_id"] = $component['ui_node_id'];
                        TicketFormItem::create($ticket_form_item);
                    } else {
                        // return $component;
                        $ticket_form_item["ticket_form_id"] = $ticket_form_id;
                        $ticket_form_item["data_type"] = $component['class'];
                        $ticket_form_item["sequence"] = $key + 1;
                        $ticket_form_item["ui_node_id"] = $component['ui_node_id'];
                        $ticket_form_item["lable"] = $component['data']["label"];
                        $ticket_form_item["place_holder"] = (array_key_exists("placeholder", $component["data"])) ? $component["data"]["placeholder"] : "";
                        $ticket_form = TicketFormItem::create($ticket_form_item);
                        if ($component["class"] == "Radio" || $component["class"] == "Dropdown" || $component["class"] == "Checkbox") {
                            foreach ($component["options"] as $option) {
                                $form_options["ticket_form_item_id"] = $ticket_form->id;
                                $form_options["option"] = $option;
                                TicketFormOption::create($form_options);
                            }
                        }
                    }
                }
            }

            foreach ($components as $key => $component) {
                if ($component["class"] != "Start") {
                    $ivr_component = TicketFormItem::where(["ui_node_id" => $key, "ticket_form_id" => $ticket_form_id])->first();
                    $parent_ivr_component = TicketFormItem::where(["ui_node_id" => $component["inputs"][0]["node"], "ticket_form_id" => $ticket_form_id])->first();
                    $ticket_form_item_update = TicketFormItem::find($ivr_component->id);
                    $ticket_form_item_update->parent_id = $parent_ivr_component->id;
                    $ticket_form_item_update->save();
                }

                if ($component["class"] != "Start") {
                    if ($component["class"] == "Radio" || $component["class"] == "Dropdown" || $component["class"] == "Checkbox") {
                        if (!empty($component["outputs"])) {
                            foreach ($component["outputs"] as $out_put) {
                                $next_component = $components[$out_put["node"]];
                                if ($next_component["class"] != "Stop") {
                                    if ($next_component["data"]["selectednode"] == null) {
                                        throw ValidationException::withMessages([$next_component["data"]['label'] . " must has configuration prompt"]);
                                    } else {
                                        $next_ticket_component = TicketFormItem::where(["ui_node_id" => $out_put["node"], "ticket_form_id" => $ticket_form_id])->first();
                                        // return $next_ticket_component;
                                        $selection = TicketFormOption::where([
                                            "option" => $next_component["data"]["selectednode"],
                                            "ticket_form_item_id" => $ivr_component->id
                                        ])->first();
                                        // $ticket_relation["selection"] = $next_component["data"]["selectednode"];
                                        $ticket_relation["child_form_id"] = $next_ticket_component->id;
                                        $ticket_relation["parent_form_id"] = $ivr_component->id;
                                        $ticket_relation["ticket_form_option_id"] = $selection->id;
                                        $relationship_exist = TicketFormRelationship::where($ticket_relation)->first();
                                        if (!$relationship_exist)
                                            TicketFormRelationship::create($ticket_relation);
                                    }
                                }
                            }
                        } else {
                            // throw ValidationException::withMessages([$component["data"]['name'] . " you can't end flow with a background"]);
                        }
                    }
                }
            }
            $ivr_ui = TicketFormUIJson::where("ticket_form_id", $ticket_form_id)->first();
            if (!$ivr_ui) {
                TicketFormUIJson::Create([
                    "ticket_form_id" => $ticket_form_id,
                    "json_ui" => $ui_json
                ]);
            } else {
                TicketFormUIJson::where("ticket_form_id", $ticket_form_id)->update(["json_ui" => $ui_json]);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * It gets all the form items from the database and returns them in a format that is easy to use in the
     * front end
     * 
     * @param Request request The request object.
     * 
     * @return The form items are being returned.
     */
    public function get_form_items(Request $request)
    {
        $start = TicketFormItem::where([
            "ticket_form_id" => $request->ticket_form_id,
            "data_type" => "Start"
        ])->first();
        $form_items = TicketFormItem::with("ticket_form_option")->where([
            "ticket_form_id" => $request->ticket_form_id
        ])->get();

        $form_to_return = array();

        foreach ($form_items as $form_item) {
            if ($form_item->data_type != "Start" && $form_item->data_type != "Stop") {
                if ($form_item->data_type == "Radio" || $form_item->data_type == "Dropdown" || $form_item->data_type == "Checkbox") {
                    $form_to_return[] = $form_item;
                    $index = count($form_to_return) - 1;
                    $relation_ship = TicketFormRelationship::where("parent_form_id", $form_item->id)->get();
                    $form_to_return[$index]["relation_rule"] = $relation_ship;
                    $form_to_return[$index]["child"] = $this->_get_child($form_item->id);
                } else {
                    $relation_ship = TicketFormRelationship::where("child_form_id", $form_item->id)->get();
                    if (!$relation_ship)
                        $form_to_return[] = $form_item;
                }
            }
        }
        return $form_to_return;
    }

    /**
     * It gets all the child items of a parent item
     * 
     * @param parent_id The id of the parent item.
     * 
     * @return A collection of TicketFormItem objects.
     */
    public function _get_child($parent_id)
    {
        return TicketFormItem::with("ticket_form_option")->where([
            "parent_id" => $parent_id
        ])->get();
    }

    /**
     * It returns the JSON for a ticket form
     * 
     * @param Request request The request object
     * 
     * @return The ticket form json for the given ticket form id.
     */
    public function get_ticket_form_json(Request $request)
    {
        $request->validate([
            "ticket_form_id" => "required|exists:ticket_forms,id"
        ]);
        return TicketFormUIJson::where("ticket_form_id", $request->ticket_form_id)->first();
    }

    /**
     * It creates a ticket
     * 
     * @param Request request The request object.
     * 
     * @return A ticket is being returned.
     */
    public function create_ticket(Request $request)
    {
        $request->validate([
            "ticket_form_id" => "required|exists:ticket_forms,id",
            "account_id" => "nullable|exists:accounts,id",
            "channel_id" => "required|integer|exists:channels,id",
            "interaction_reference" => "required",
            ////chat id or call log id
            "contact" => "nullable|string",
            ////phone number or social chat id(id from channels)
            "contact_id" => "nullable|exists:contacts,id",
            "status" => "required|string|in:PENDING,ESCALATED,RESOLVED",
            "notify_me_at" => "nullable|required_if:status,PENDING",
            "ticket_entry" => "array"
        ]);

        $formatted_phone = $request->contact;
        if ($request->channel == 1 || $request->channel == 6 || $request->channel == 7) {
            $formatted_phone = PhoneFormatterService::format_phone($request->contact);
        }

        $company_id = Auth::user()->company_id;

        $ticket_entry_data = $request->ticket_entry;

        $ticket_form = TicketForm::find($request->ticket_form_id);

        if ($ticket_form->company_id != $company_id) {
            throw ValidationException::withMessages(["Ticket form does not belong to your company!"]);
        }

        TicketFormItem::where("ticket_form_id", $request->ticket_form_id)->get();

        /* Validating the ticket entry data. */
        $this->_validate_ticket_entries($ticket_entry_data, $request->ticket_form_id);

        ////creating ticket
        $newTicket = Ticket::create([
            "ticket_number" => $this->generateTicketNumber(),
            "account_id" => $request->account_id,
            "contact" => $formatted_phone,
            "created_by" => Auth::user()->id,
            "status" => $request->status,
            "company_id" => $company_id,
            "channel_id" => $request->channel_id,
            "contact_id" => $request->contact_id,
            "interaction_id" => $request->interaction_reference
        ]);

        TicketInteraction::create([
            "interaction_code" => $this->generateTicketInteractionCode(),
            "company_id" => $company_id,
            "ticket_id" => $newTicket->id,
            "channel_id" => $request->channel_id,
            "contact" => $formatted_phone,
            "interaction_reference" => $request->interaction_reference
        ]);

        /* Creating a ticket entry for each form item. */
        foreach ($ticket_entry_data as $form_id => $ticket_entry) {
            $form_item = TicketFormItem::where(["ui_node_id" => $form_id, "ticket_form_id" => $request->ticket_form_id])->first();
            if ($form_item->data_type == "Checkbox") {
                foreach ($ticket_entry as $response) {
                    $option_id = TicketFormOption::where(["ticket_form_item_id" => $form_item->id, "option" => $response])->first();
                    $ticket_entry = TicketEntry::create([
                        "ticket_entry_id" => $newTicket->id,
                        "form_item_id" => $form_item->id,
                        "value" => $option_id->id
                    ]);
                }
            } else {
                if (
                    $form_item->data_type == "Radio" ||
                    $form_item->data_type == "Dropdown"
                ) {
                    $option_id = TicketFormOption::where(["ticket_form_item_id" => $form_item->id, "option" => $ticket_entry])->first();
                    $ticket_entry = TicketEntry::create([
                        "ticket_entry_id" => $newTicket->id,
                        "form_item_id" => $form_item->id,
                        "value" => $option_id->id
                    ]);
                } else {
                    $ticket_entry = TicketEntry::create([
                        "ticket_entry_id" => $newTicket->id,
                        "form_item_id" => $form_item->id,
                        "value" => $ticket_entry
                    ]);
                }
            }
        }

        /* Checking if the ticket status is ESCALATED. If it is, it will check the escalation matrix of the
        ticket and compare it with the escalation matrix of the escalation points. If the ticket status is
        PENDING, it will create a pending ticket. 
        */
        if ($request->status == "ESCALATED") {
            $matrix_similarity = 0;
            $escalation_point = null;
            $potential_escalation_points = EscalationPoint::where("ticket_form_id", $request->ticket_form_id)
                ->get();
            $ticket_entries = TicketEntry::where("ticket_entry_id", $newTicket->id)
                ->get(["form_item_id", "value"]);

            /* Comparing the escalation matrix of the ticket with the escalation matrix of the escalation points. */
            $potentials = array();
            $entries = array();
            /* Comparing the ticket entries with the escalation matrices of the potential escalation points. */
            if (count($potential_escalation_points) > 0) {
                foreach ($potential_escalation_points as $key => $potential_escalation_point) {

                    $escalationMatrices = json_decode($potential_escalation_point->escalation_matrix, true);

                    foreach ($escalationMatrices as $key => $escalationMatrix) {

                        array_push($potentials, $key);
                        array_push($potentials, $escalationMatrix);
                    }

                    foreach ($ticket_entries as $ticket_entry) {

                        $ticketEntry = json_decode($ticket_entry, true);

                        array_push($entries, $ticketEntry['form_item_id']);
                        array_push($entries, $ticketEntry['value']);
                    }


                    $matches = array_intersect($entries, $potentials);

                    $a = round(count($matches));
                    $b = count($potentials);
                    $similarity = $a / $b * 100;
                    if ($matrix_similarity < $similarity) {
                        $matrix_similarity = $similarity;
                        $escalation_point = $potential_escalation_point;
                    }
                    $entries = array();
                    $potentials = array();
                }
            }

            /* Checking if the escalation point is set. If it is, it will get the first escalation level for the
            form. If the escalation level is set, it will create a ticket escalation. If the escalation level is
            not set, it will return an error. */
            if ($escalation_point) {
                $escalation_level = EscalationLevel::where("escalation_point_id", $escalation_point->id)
                    ->orderBy('sequence', 'ASC')->first();

                if ($escalation_level) {
                    TicketEscalation::create([
                        "ticket_entry_id" => $newTicket->id,
                        "escalation_point_id" => $escalation_point->id,
                        "escalation_level_id" => $escalation_level->id
                    ]);

                    $ticketUpdate = Ticket::find($newTicket->id);

                    $escalation = EscalationPoint::find($escalation_point->id);

                    $ticketUpdate->priority_id = $escalation->priority_id;

                    $ticketUpdate->save();
                } else {
                    return response()->json([
                        'error' => true,
                        'message' => 'Missing escalation level for the form!'
                    ], 422);
                }
            }
        } else if ($request->status == "PENDING") {
            PendingTicket::create([
                "ticket_entry_id" => $newTicket->id,
                "agent_id" => Auth::user()->id,
                "notify_at" => $request->notify_me_at
            ]);
        }
        return response()->json([
            'success' => true,
            'message' => 'Ticket created successfully!'
        ], 200);
    }

    /**
     * It validates the ticket entry data against the ticket form items
     * 
     * @param ticket_entry_data This is the array of ticket entry data.
     * @param ticket_form_id The id of the ticket form you want to validate against.
     */
    public function _validate_ticket_entries($ticket_entry_data, $ticket_form_id)
    {
        // return $ticket_entry_data[0];
        foreach ($ticket_entry_data as $form_id => $ticket_entry) {
            $form_item = TicketFormItem::where(["ui_node_id" => $form_id, "ticket_form_id" => $ticket_form_id])->first();
            if (
                $form_item->data_type == "Radio" ||
                $form_item->data_type == "Dropdown"
            ) {
                $option_item = TicketFormOption::where(["ticket_form_item_id" => $form_item->id, "option" => $ticket_entry])->first();
                if (!$option_item) {
                    throw ValidationException::withMessages(["invalid entry for " . $form_item->lable]);
                }
            } else if ($form_item->data_type == "Checkbox") {
                $check_array = is_array($ticket_entry);
                if (!$check_array) {
                    throw ValidationException::withMessages(["invalid entry for " . $form_item->lable]);
                }
                foreach ($ticket_entry as $response) {
                    $option_item = TicketFormOption::where(["ticket_form_item_id" => $form_item->id, "option" => $response])->first();
                    if (!$option_item) {
                        throw ValidationException::withMessages(["invalid entry for " . $ticket_entry->lable]);
                    }
                }
            }
        }
    }

    /**
     * It generates a random ticket number for a ticket
     * 
     * @return A ticket number
     */
    public function generateTicketNumber()
    {
        $companyNames = explode(" ", Auth::user()->company->name);
        $initials = null;
        foreach ($companyNames as $companyName) {
            $initials .= $companyName[0];
        }
        do {
            $code = strtoupper($initials) . '-' . Str::random(6);
        } while (Ticket::where("ticket_number", "=", $code)->first());

        return $code;
    }

    /**
     * It generates a unique code for a ticket interaction
     * 
     * @return A string of the form "ABC-XXXXXX" where ABC is the initials of the company name and
     * XXXXXX is a random string of 6 characters.
     */
    public function generateTicketInteractionCode()
    {
        $companyNames = explode(" ", Auth::user()->company->name);
        $initials = null;
        foreach ($companyNames as $companyName) {
            $initials .= $companyName[0];
        }
        do {
            $code = strtoupper($initials) . '-' . Str::random(6);
        } while (TicketInteraction::where("interaction_code", "=", $code)->first());

        return $code;
    }

    /**
     * It takes a ticket id, a channel id and an interaction reference and saves it to the database
     * 
     * @param Request request The request object
     * 
     * @return A JSON response with a success message.
     */
    public function add_ticket_interactions(Request $request)
    {
        $request->validate([
            'ticket_id' => 'required|exists:tickets,id',
            'channel_id' => 'required|exists:channels,id',
            'interaction_reference' => 'required'
        ]);

        $formatted_phone = $request->interaction_reference;
        if ($request->channel_id == 1 || $request->channel_id == 6 || $request->channel_id == 7) {
            $formatted_phone = PhoneFormatterService::format_phone($request->interaction_reference);
        }
        $interaction = TicketInteraction::create([
            "interaction_code" => $this->generateTicketInteractionCode(),
            "company_id" => Auth::user()->company_id,
            "ticket_id" => $request->ticket_id,
            "channel_id" => $request->channel_id,
            "interaction_reference" => $formatted_phone
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ticket interaction saved successfully!'
        ], 200);
    }
}