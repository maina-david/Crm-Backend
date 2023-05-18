<?php

namespace App\Http\Controllers\quality_assurance\reports;

use App\Http\Controllers\Controller;
use App\Http\Resources\AgentAverageReviewResource;
use App\Http\Resources\AgentReportPerAttributeResource;
use App\Models\QAForm;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AgentReportController extends Controller
{
    /**
     * It returns the average review score of all agents in the company, with the number of reviews they
     * have received
     * 
     * @param Request request The request object.
     * 
     * @return A collection of agents with their average review score and the total number of reviews they
     * have received.
     */
    public function agentAverageReviewReport(Request $request)
    {
        $request->validate([
            'from' => 'required_with:to|date_format:Y-m-d|before:to',
            'to' => 'required_with:from|date_format:Y-m-d|after:from',
            'queue_type' => 'string|in:chat,voice|required_with:queue_id',
            'queue_id' => 'integer|required_with:queue_type'
        ]);

        $agents = User::where('company_id', Auth::user()->company_id)
            ->wherehas('reviews')
            ->withCount(['reviews' => function ($query) use ($request) {
                $query->when($request->has('from') && $request->has('to'), function ($q) use ($request) {
                    return $q->whereDate('created_at', '>=', $request->from)
                        ->whereDate('created_at', '<=', $request->to);
                });
                $query->when($request->has('queue_type') && $request->has('queue_id'), function ($q) use ($request) {
                    return $q->where('queue_type', '=',  $request->queue_type)
                        ->where('queue_id', '=', $request->queue_id);
                });
            }])
            ->withAvg(['reviews as average_score' => function ($query) use ($request) {
                $query->when($request->has('from') && $request->has('to'), function ($q) use ($request) {
                    return $q->whereDate('created_at', '>=', $request->from)
                        ->whereDate('created_at', '<=', $request->to);
                });
                $query->when($request->has('queue_type') && $request->has('queue_id'), function ($q) use ($request) {
                    return $q->where('queue_type', '=',  $request->queue_type)
                        ->where('queue_id', '=', $request->queue_id);
                });
            }], 'assessment_total')->get();

        return response()->json(AgentAverageReviewResource::collection($agents), 200);
    }

    /**
     * It returns a collection of agents with their reviews for a specific QA Form
     * 
     * @param Request request The request object.
     * 
     * @return A collection of agents with their reviews.
     */
    public function agentReportPerAttribute(Request $request)
    {
        $request->validate([
            'form_id' => 'required|exists:q_a_forms,id',
            'from' => 'required_with:to|date_format:Y-m-d|before:to',
            'to' => 'required_with:from|date_format:Y-m-d|after:from',
            'queue_type' => 'string|in:chat,voice|required_with:queue_id',
            'queue_id' => 'integer|required_with:queue_type'
        ]);

        $form = QAForm::find($request->form_id);

        if ($form->company_id != Auth::user()->company_id) {
            return response()->json(['message' => 'QA Form does not belong to your company!'], 401);
        }

        $agents = User::where('company_id', Auth::user()->company_id)
            ->wherehas('reviews', function ($query) use ($request) {
                $query->when($request->has('from') && $request->has('to'), function ($q) use ($request) {
                    return $q->whereDate('created_at', '>=', $request->from)
                        ->whereDate('created_at', '<=', $request->to);
                });
                $query->when($request->has('queue_type') && $request->has('queue_id'), function ($q) use ($request) {
                    return $q->where('queue_type', '=',  $request->queue_type)
                        ->where('queue_id', '=', $request->queue_id);
                });
                $query->where('qa_form_id', $request->form_id);
            })
            ->with('reviews', function ($query) use ($request) {
                $query->when($request->has('from') && $request->has('to'), function ($q) use ($request) {
                    return $q->whereDate('created_at', '>=', $request->from)
                        ->whereDate('created_at', '<=', $request->to);
                });
                $query->when($request->has('queue_type') && $request->has('queue_id'), function ($q) use ($request) {
                    return $q->where('queue_type', '=',  $request->queue_type)
                        ->where('queue_id', '=', $request->queue_id);
                });
                $query->where('qa_form_id', $request->form_id);
            })
            ->get();

        return response()->json(AgentReportPerAttributeResource::collection($agents), 200);
    }

    /**
     * It returns the agent's details along with the reviews he/she has received within the specified date
     * range
     * 
     * @param Request request The request object.
     * 
     * @return The agent's reviews.
     */
    public function agentDetailedReport(Request $request)
    {
        $request->validate([
            'agent_id' => 'required|exists:users,id',
            'from' => 'required_with:to|date_format:Y-m-d|before:to',
            'to' => 'required_with:from|date_format:Y-m-d|after:from',
            'queue_type' => 'string|in:chat,voice|required_with:queue_id',
            'queue_id' => 'integer|required_with:queue_type'
        ], [
            'agent_id.exists' => 'This agent does not exist!'
        ]);

        $agent = User::find($request->agent_id);

        if ($agent->company_id != Auth::user()->company_id) {
            return response()->json(['message' => 'Agent does not belong to your company!'], 401);
        }

        $agent->load(['reviews' => function ($query) use ($request) {
            $query->when($request->has('from') && $request->has('to'), function ($q) use ($request) {
                return $q->whereDate('created_at', '>=', $request->from)
                    ->whereDate('created_at', '<=', $request->to);
            });
            $query->when($request->has('queue_type') && $request->has('queue_id'), function ($q) use ($request) {
                return $q->where('queue_type', '=',  $request->queue_type)
                    ->where('queue_id', '=', $request->queue_id);
            });
        }]);

        return response()->json($agent, 200);
    }
}