<?php

namespace App\Http\Controllers\quality_assurance;

use App\Http\Controllers\Controller;
use App\Models\AssignedConversation;
use App\Models\Conversation;
use App\Models\QAForm;
use App\Models\QAFormAttr;
use App\Models\QATeam;
use App\Models\QATeamMember;
use App\Models\QAEvaluation;
use App\Models\QAEvaluationDetail;
use App\Models\QAInteractionReview;
use App\Models\QATeamQueue;
use App\Models\QueueLog;
use App\Models\User;
use Auth;
use DB;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Rules\Boolean;
use Illuminate\Support\Facades\Log;

class QAFormController extends Controller
{
    /**
     * This function creates a quality assurance form
     * 
     * @param Request request The request object.
     * 
     * @return A JSON response with a message and a status code.
     */
    public function create_q_a_form(Request $request)
    {
        $request->validate([
            "name" => "required|string|max:255",
            "description" => "required|string|max:255"
        ]);

        $check_duplicate = QAForm::where(["name" => $request->name, "company_id" => Auth::user()->company_id])->first();

        if ($check_duplicate) {
            return response()->json(['message' => "You have Qality assurance form with the same name!"], 422);
        }

        QAForm::create([
            "name" => $request->name,
            "description" => $request->description,
            "company_id" => Auth::user()->company_id
        ]);

        return response()->json("Successfully created!", 200);
    }

    /**
     * It updates a quality assurance form
     * 
     * @param Request request The request object
     */
    public function update_q_a_form(Request $request)
    {
        $request->validate([
            "q_a_form_id" => "required|exists:q_a_forms,id",
            "name" => "required|string|max:255",
            "description" => "required|string|max:255"
        ]);

        $check_duplicate = QAForm::where(["name" => $request->name, "company_id" => Auth::user()->company_id])->first();

        if ($check_duplicate) {
            if ($check_duplicate->id != $request->q_a_form_id)
                return response()->json(['message' => "You have Quality assurance form with the same name!"], 422);
        }

        $item_to_update = QAForm::find($request->q_a_form_id);
        if ($item_to_update->company_id != Auth::user()->company_id) {
            return response()->json(['message' => "The QA team doesn't belong to you!"], 401);
        }

        $item_to_update->update([
            "name" => $request->name,
            "description" => $request->description
        ]);
        return response()->json("Successfully updated!", 200);
    }

    /**
     * It returns all the QAForms that belong to the company that the user is logged in to
     */
    public function get_q_a_form()
    {
        return QAForm::with("q_a_form_items")->where("company_id", Auth::user()->company_id)->get();
    }

    /**
     * It adds items to a quality assurance form
     * 
     * @param Request request the request object
     * 
     * @return Successfully added
     */
    public function add_items_to_qa_form(Request $request)
    {
        $request->validate([
            "q_a_form_id" => "required|exists:q_a_forms,id",
            "form_items" => "required|array"
        ]);

        try {
            DB::beginTransaction();
            foreach ($request->form_items as $key => $form_item) {
                $form_item_request = new Request($form_item);
                $form_item_request->validate([
                    'question' => 'required|string|max:100',
                    'question_type' => 'required|in:range,toggle',
                    'weight' => 'required|integer|min:1',
                    'max_range' => 'integer|min:1|required_if:question_type,range'
                ]);
                $QAForm_attr = new QAFormAttr();
                $QAForm_attr->q_a_form_id = $request->q_a_form_id;
                $QAForm_attr->question = $form_item["question"];
                $QAForm_attr->type = $form_item["question_type"];
                $QAForm_attr->weight = $form_item["weight"];
                $QAForm_attr->is_required = $form_item["is_required"];
                if ($form_item_request->has('max_range')) {
                    $QAForm_attr->range = $form_item["max_range"];
                }
                $QAForm_attr->save();
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
        return response()->json([
            'success' => true,
            'message' => 'QA Form Attributes added successfully!'
        ], 200);
    }

    /**
     * It updates the quality assurance form attributes
     * 
     * @param Request request the request object
     * @param id The id of the item to be updated
     * 
     * @return Successfully updated
     */
    public function update_items_to_qa_form(Request $request)
    {
        $request->validate([
            "q_a_form_attr_id" => "required|exists:q_a_form_attrs,id",
            "q_a_form_id" => "required|exists:q_a_forms,id",
            "question" => "required|string",
            "question_type" => "required|string|in:toggle,range",
            "weight" => "required|integer",
            "range" => "required|integer",
        ]);

        $item_to_update = QAFormAttr::find($request->q_a_form_attr_id);

        $item_to_update->update(
            [
                "q_a_form_id" => $request->q_a_form_id,
                "question" => $request->question,
                "type" => $request->question_type,
                "weight" => $request->weight,
                "range" => $request->range
            ]
        );
        return response()->json("Successfully updated", 200);
    }

    /**
     * It gets a QA form by its ID, and returns it
     * 
     * @param Request request The request object
     * 
     * @return The question and answer form with the items.
     */
    public function get_qa_form_by_id(Request $request)
    {
        $request->validate([
            "q_a_form_id" => "required|exists:q_a_forms,id"
        ]);
        $q_a = QAForm::with("q_a_form_items")->find($request->q_a_form_id);
        if ($q_a->company_id != Auth::user()->company_id) {
            return response()->json(['message' => "The form doesn't belong to you!"], 401);
        }
        return $q_a;
    }

    /**
     * It adds a QA form response to the database
     * 
     * @param Request request 
     */
    public function add_qa_form_response(Request $request)
    {
        $request->validate([
            'review_id' => 'required|exists:q_a_interaction_reviews,id',
            'interaction_reference' => 'required|exists:interactions,interaction_reference',
            'responses' => 'required|array'
        ]);

        $interactionReview = QAInteractionReview::find($request->review_id);

        $isMember = QATeamMember::where([
            'member_id' => Auth::user()->id,
            'q_a_team_id' => $interactionReview->q_a_team_id
        ])->first();

        if (!$isMember) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to add responses to this QA review!'
            ], 401);
        }

        $QATeam = QATeam::find($interactionReview->q_a_team_id);

        if ($QATeam->q_a_form_id == NULL) {
            return response()->json([
                'success' => false,
                'message' => 'Your team does not have a valid QA form!'
            ], 422);
        }

        if ($interactionReview->interaction->interaction_reference != $request->interaction_reference) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Review and interaction reference combination!'
            ], 422);
        }

        if ($interactionReview->status == 'REVIEWED') {
            return response()->json([
                'success' => false,
                'message' => 'QA Review already submitted!'
            ], 422);
        }

        $responses = $request->responses;
        try {
            DB::beginTransaction();

            $score = 0;

            foreach ($responses as $response) {

                $response_request = new Request($response);
                $response_request->validate([
                    'form_attr_id' => 'required|exists:q_a_form_attrs,id',
                    'response' => 'required',
                    'comment' => 'required|string'
                ]);

                $form_item = QAFormAttr::find($response_request->form_attr_id);

                if ($form_item->type == 'toggle') {
                    if ($response_request->response == true || $response_request->response == false) {
                        $score += ($response_request->response == true) ? $form_item->weight : 0;
                    } else {
                        throw ValidationException::withMessages(['The response for ' . $form_item->question . ' should be true or false']);
                    }
                } else {
                    if ($response_request->response > $form_item->range || $response_request->response < 0) {
                        throw ValidationException::withMessages(['The response for ' . $form_item->question . ' should be less than or equal to ' . $form_item->range]);
                    }
                    $score +=  $form_item->weight * $response_request->response;
                }
            }

            $agentID = NULL;
            $queueType = '';
            $queueId = '';
            if ($interactionReview->interaction->channel_id == 7) {
                $call = QueueLog::where('call_id', $request->interaction_reference)->first();
                if ($call) {
                    $agentID = $call->agent->id;
                    $queueType = 'voice';
                    $queueId = $call->queue_id;
                }
            } else {

                $assignedConversation = AssignedConversation::where('conversation_id', $request->interaction_reference)
                    ->where(function ($conversation) {
                        $conversation->whereNotNull('first_response');
                    })->first();

                if ($assignedConversation) {
                    $agentID = User::find($assignedConversation->agent_id)->id;
                    $queueType = 'chat';
                    $queueId = $assignedConversation->conversation->queue->id;
                } else {
                    $conversation = Conversation::find($request->interaction_reference);
                    if ($conversation) {
                        $agentID = User::find($conversation->assigned_to)->id;
                        $queueType = 'chat';
                        $queueId = $conversation->queue->id;
                    }
                }
            }

            if ($agentID != NULL) {

                $qa_evaluation = QAEvaluation::create([
                    'qa_team_id' => $interactionReview->q_a_team_id,
                    'queue_type' => $queueType,
                    'queue_id' => $queueId,
                    'qa_form_id' => $QATeam->q_a_form_id,
                    'agent_id' => $agentID,
                    'assessed_by' => Auth::user()->id,
                    'review_id' => $request->review_id,
                    'assessment_total' => $score
                ]);

                $assignedTime = QAInteractionReview::find($request->review_id)->created_at;

                $closedTime = $qa_evaluation->created_at;

                $handling_time = $assignedTime->diffInSeconds($closedTime);

                $qa_evaluation->update(['handling_time' => $handling_time]);

                foreach ($responses as $key => $response) {
                    ////////////validate response
                    $form_item = QAFormAttr::find($response["form_attr_id"]);
                    $score = ($response["response"] == true) ? $form_item->weight : 0;
                    $qa_evaluation_details = QAEvaluationDetail::create([
                        "qa_evaluation_id" => $qa_evaluation->id,
                        "form_item_id" => $form_item->id,
                        "score" => $response["response"],
                        "result" => $score,
                        "comment" => $response["comment"]
                    ]);
                    ////////////end validate items
                }

                /* Updating the interaction review status to reviewed. */
                $interactionReview->interaction()->update(['reviewed' => true]);

                $interactionReview->status = 'REVIEWED';
                $interactionReview->save();

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'QA Interaction Review saved successfully!'
                ], 200);
            } else {
                Log::alert("Agent ID is NULL");
            }
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}