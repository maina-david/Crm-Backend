<?php

namespace App\Http\Controllers\quality_assurance;

use App\Http\Controllers\Controller;
use App\Http\Resources\QAInteractionReviewResource;
use App\Http\Resources\QAInteractionDetailResource;
use App\Http\Resources\QAReviewDetailResource;
use App\Models\Interaction;
use App\Models\QAInteractionReview;
use App\Models\QASetting;
use App\Models\QATeamMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class QAController extends Controller
{
    /**
     * It returns all the open reviews for the user
     * 
     * @return A collection of QAInteractionReviews that are either NOT-REVIEWED or UNDER-REVIEW.
     */
    public function list_open_reviews()
    {
        $userTeams = QATeamMember::where('member_id', Auth::user()->id)->get('q_a_team_id');

        if ($userTeams->count() > 0) {
            $openReviews = QAInteractionReview::whereIn('q_a_team_id', $userTeams)
                ->where(function ($query) {
                    $query->where('status', 'NOT-REVIEWED')
                        ->where('company_id', Auth::user()->company_id);
                })
                ->orWhere(function ($query) {
                    $query->where('status', 'UNDER-REVIEW')
                        ->where('agent_id', Auth::user()->id);
                })
                ->get();

            return response()->json(QAInteractionReviewResource::collection($openReviews), 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'You do not belong to any QA Team!'
        ], 200);
    }

    /**
     * It returns a list of all the closed reviews for the current user
     * 
     * @return A collection of QAInteractionReviews that are reviewed and belong to the user's company.
     */
    public function list_closed_reviews()
    {
        $userTeams = QATeamMember::where('member_id', Auth::user()->id)->get('q_a_team_id');

        if ($userTeams->count() > 0) {
            $closedReviews =  QAInteractionReview::whereIn('q_a_team_id', $userTeams)
                ->where(function ($query) {
                    $query->where('company_id', '=', Auth::user()->company_id)
                        ->where('status', '=', 'REVIEWED');
                })->get();

            return response()->json(QAInteractionReviewResource::collection($closedReviews), 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'You do not belong to any QA Team!'
        ], 200);
    }

    /**
     * It gets the interaction details for the given interaction reference
     * 
     * @param Request request The request object
     * 
     * @return A JSON response with the interaction details.
     */
    public function get_interaction_details(Request $request)
    {
        $request->validate([
            'interaction_reference' => 'required|exists:interactions,interaction_reference'
        ], [
            'interaction_reference.exists' => 'Interaction does not exist!'
        ]);

        $userTeams = QATeamMember::where('member_id', Auth::user()->id)->get('q_a_team_id');

        $interaction = Interaction::where('interaction_reference', $request->interaction_reference)
            ->whereHas('review', function (Builder $query) use ($userTeams) {
                $query->whereIn('q_a_team_id', $userTeams)
                    ->where(function ($q) {
                        $q->where('status', '=', 'NOT-REVIEWED')
                            ->orWhere('status', '=', 'UNDER-REVIEW');
                    });
            })
            ->first();

        if ($interaction) {
            $interaction->review()->update([
                'agent_id' => Auth::user()->id,
                'status' => 'UNDER-REVIEW'
            ]);

            return response()->json([
                'success' => true,
                'data' => new QAInteractionDetailResource($interaction)
            ], 200);
        }
        return response()->json([
            'success' => false,
            'message' => 'Unable to retrieve this interaction!'
        ], 401);
    }

    /**
     * It returns the details of a QA Interaction Review
     * 
     * @param Request request The request object
     * 
     * @return A QAReviewDetailResource
     */
    public function show_review_details(Request $request)
    {

        $request->validate([
            'review_id' => 'required|exists:q_a_interaction_reviews,id'
        ]);

        $review = QAInteractionReview::find($request->review_id);

        if ($review->status == 'NOT-REVIEWED') {
            return response()->json([
                'success' => false,
                'message' => 'This QA Interaction has not been reviewed yet'
            ], 406);
        }

        return response()->json(new QAReviewDetailResource($review), 200);
    }

    /**
     * > This function returns all the QA settings for the company that the logged in user belongs to
     * 
     * @return A collection of QASettings
     */
    public function listQASettings()
    {
        return QASetting::where('company_id', Auth::user()->company_id)->get();
    }

    /**
     * It saves the QA settings for the company
     * 
     * @param Request request The request object
     */
    public function saveQASettings(Request $request)
    {
        $request->validate([
            'max_interactions' => 'required|integer',
            'frequency' => 'required|integer|min:1|max:14'
        ]);

        $check = QASetting::where('company_id', Auth::user()->company_id)->get();

        if ($check->count() > 0) {
            return response()->json([
                'message' => 'You already have existing QA settings!'
            ], 422);
        }

        $QASetting = QASetting::create([
            'company_id' => Auth::user()->company_id,
            'max_interactions' => $request->max_interactions,
            'frequency' => $request->frequency
        ]);

        return response()->json([
            'success' => true,
            'message' => 'QA settings saved successfully!'
        ], 200);
    }

    /**
     * It updates the QA settings of a company
     * 
     * @param Request request The request object.
     * @param id The id of the QA settings you want to update.
     * 
     * @return The QA settings are being returned.
     */
    public function updateQASettings(Request $request)
    {
        $request->validate([
            'max_interactions' => 'required|integer',
            'frequency' => 'required|integer|min:1|max:14'
        ]);

        $QASetting = QASetting::where('company_id', Auth::user()->company_id)->first();

        if (!$QASetting) {
            return response()->json([
                'message' => 'You dont have any configured QA settings!'
            ], 401);
        }

        $QASetting->update([
            'max_interactions' => $request->max_interactions,
            'frequency' => $request->frequency
        ]);

        return response()->json([
            'success' => true,
            'message' => 'QA settings updated successfully!'
        ], 200);
    }

    /**
     * It deletes a QA setting
     * 
     * @param id The id of the QA settings you want to delete.
     */
    public function deleteQASettings()
    {
        $QASetting = QASetting::where('company_id', Auth::user()->company_id)->first();

        if (!$QASetting) {
            return response()->json([
                'message' => 'You dont have any configured QA settings!'
            ], 401);
        }
        $QASetting->delete();

        return response()->json([
            'success' => true,
            'message' => 'QA settings deleted successfully!'
        ], 200);
    }
}