<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class QATeamMemberReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $teamMember = [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone_number' => $this->phone_number
        ];

        if ($request->has('from') && $request->has('to')) {
            $teamMember['total_reviews'] = $this->evaluations->where('created_at', '>=', $request->get('from') . ' 00.00.00')
                ->where('created_at', '<=', $request->get('to') . ' 23.59.59')
                ->count();
            $teamMember['ongoing_reviews'] = $this->interaction_reviews->where('created_at', '>=', $request->get('from') . ' 00.00.00')
                ->where('created_at', '<=', $request->get('to') . ' 23.59.59')
                ->where('agent_id', $this->id)
                ->where('status', 'UNDER-REVIEW')
                ->count();
            $teamMember['agents_reviewed'] = $this->evaluations->where('created_at', '>=', $request->get('from') . ' 00.00.00')
                ->where('created_at', '<=', $request->get('to') . ' 23.59.59')
                ->groupBy('agent_id')
                ->count();
            $averageHandlingTime = $this->evaluations->where('created_at', '>=', $request->get('from') . ' 00.00.00')
                ->where('created_at', '<=', $request->get('to') . ' 23.59.59')
                ->avg('handling_time');
            $teamMember['average_handling_time'] = (isset($averageHandlingTime) ? gmdate('H:i:s', $averageHandlingTime) : gmdate('H:i:s', 0));
            $scores = $this->evaluations->average("assessment_total");
            $teamMember["average_score"] = (isset($scores) ? $scores : 0);
        } else {
            $teamMember['total_reviews'] = $this->evaluations->count();
            $teamMember['ongoing_reviews'] = $this->interaction_reviews->where('agent_id', $this->id)->where('status', 'UNDER-REVIEW')->count();
            $teamMember['agents_reviewed'] = $this->evaluations->groupBy('agent_id')->count();
            $averageHandlingTime = $this->evaluations->avg('handling_time');
            $teamMember['average_handling_time'] = (isset($averageHandlingTime) ? gmdate('H:i:s', $averageHandlingTime) : gmdate('H:i:s', 0));
            $average_score = $this->evaluations->assessment_total->average();
            $scores = $this->evaluations->average("assessment_total");
            $teamMember["average_score"] = $scores;
        }

        return $teamMember;
    }
}
