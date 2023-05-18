<?php

namespace App\Http\Resources;

use App\Models\QAEvaluationDetail;
use App\Models\QAFormAttr;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class ReviewerScoringReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $reviewer = [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'average_score' => $this->average_score
        ];

        $evaluations = $this->evaluations;

        $formAttrs = QAFormAttr::whereIn('q_a_form_id', $evaluations->unique('qa_form_id')->pluck('qa_form_id'))
            ->get();

        foreach ($formAttrs as $key => $formAttr) {

            $average = QAEvaluationDetail::where('form_item_id', $formAttr->id)
                ->whereIn('qa_evaluation_id', $evaluations->pluck('id'))
                ->when($request->has('from') && $request->has('to'), function ($query) use ($request) {
                    return $query->whereDate('created_at', '>=', $request->from)
                        ->whereDate('created_at', '<=', $request->to);
                })
                ->avg('score');

            $reviewer['average_reviews'][$formAttr->question] = $average;
        }

        return $reviewer;
    }
}