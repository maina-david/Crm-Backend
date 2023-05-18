<?php

namespace App\Http\Resources;

use App\Models\QAEvaluation;
use App\Models\QAEvaluationDetail;
use App\Models\QAFormAttr;
use Illuminate\Http\Resources\Json\JsonResource;

class AgentReportPerAttributeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $agentReview = [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone_number' => $this->phone_number
        ];

        $formAttrs = QAFormAttr::where('q_a_form_id', $request->get('form_id'))->get();

        foreach ($formAttrs as $key => $formAttr) {

            $evaluations = QAEvaluation::where([
                'qa_form_id' => $request->get('form_id'),
                'agent_id' => $this->id
            ])->get('id');

            $average = QAEvaluationDetail::where('form_item_id', $formAttr->id)
                ->whereIn('qa_evaluation_id', $evaluations)
                ->avg('score');

            $agentReview['average_reviews'][$formAttr->question] = $average;
        }

        return $agentReview;
    }
}