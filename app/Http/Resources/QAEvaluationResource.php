<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class QAEvaluationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'reviewed_by' => $this->assessedBy->name,
            'reviewed_on' => $this->created_at,
            'assessment_total' => $this->assessment_total,
            'reviews' => QAEvaluationResponseResource::collection($this->evaluationDetails)
        ];
    }
}