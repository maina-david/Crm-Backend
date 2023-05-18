<?php

namespace App\Http\Controllers\quality_assurance\reports;

use App\Http\Controllers\Controller;
use App\Http\Resources\QATeamReviewResource;
use App\Http\Resources\ReviewerReportResource;
use App\Http\Resources\ReviewerScoringReportResource;
use App\Http\Resources\TeamPerfomanceResource;
use App\Models\QATeam;
use App\Models\User;
use Auth;
use Illuminate\Http\Request;

class TeamReportController extends Controller
{

    public function teamPerformanceReport(Request $request)
    {
        $request->validate([
            'from' => 'required_with:to|date_format:Y-m-d|before:to',
            'to' => 'required_with:from|date_format:Y-m-d|after:from'
        ]);

        $QATeams = QATeam::where('company_id', Auth::user()->company_id)
            ->get();

        return response()->json(TeamPerfomanceResource::collection($QATeams), 200);
    }
    /**
     * This function returns a collection of QATeamReviewResource objects.
     * 
     * @param Request request The request object.
     * 
     * @return A collection of QATeamReviewResource
     */
    public function teamMemberReviews(Request $request)
    {
        $request->validate([
            'from' => 'required_with:to|date_format:Y-m-d|before:to',
            'to' => 'required_with:from|date_format:Y-m-d|after:from'
        ]);

        $QATeams = QATeam::where('company_id', Auth::user()->company_id)
            ->get();

        return response()->json(QATeamReviewResource::collection($QATeams), 200);
    }

    public function reviewerScoringReport(Request $request)
    {
        $request->validate([
            'from' => 'required_with:to|date_format:Y-m-d|before:to',
            'to' => 'required_with:from|date_format:Y-m-d|after:from'
        ]);

        $agents = User::where('company_id', Auth::user()->company_id)
            ->whereHas('evaluations')
            ->withAvg(['reviews as average_score' => function ($query) use ($request) {
                $query->when($request->has('from') && $request->has('to'), function ($q) use ($request) {
                    return $q->whereDate('created_at', '>=', $request->from)
                        ->whereDate('created_at', '<=', $request->to);
                });
            }], 'assessment_total')
            ->with(['evaluations' => function ($query) use ($request) {
                $query->when($request->has('from') && $request->has('to'), function ($q) use ($request) {
                    return $q->whereDate('created_at', '>=', $request->from)
                        ->whereDate('created_at', '<=', $request->to);
                });
            }])
            ->get();

        return response()->json(ReviewerScoringReportResource::collection($agents), 200);
    }

    public function individualReviewerReport(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'from' => 'required_with:to|date_format:Y-m-d|before:to',
            'to' => 'required_with:from|date_format:Y-m-d|after:from'
        ]);

        $user = User::find($request->user_id);

        if ($user->company_id != Auth::user()->company_id) {
            return response()->json([
                'messsage' => 'User does not belong to your company!'
            ], 401);
        }

        $user->load(['evaluations' => function ($query) use ($request) {
            $query->when($request->has('from') && $request->has('to'), function ($q) use ($request) {
                return $q->whereDate('created_at', '>=', $request->from)
                    ->whereDate('created_at', '<=', $request->to);
            });
        }]);

        return response()->json(new ReviewerReportResource($user), 200);
    }
}