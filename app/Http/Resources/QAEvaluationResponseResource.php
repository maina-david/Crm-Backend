<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class QAEvaluationResponseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $responses = [
            'question' => $this->form_item->question,
            'type' => $this->form_item->type,
            'weight' => $this->form_item->weight
        ];

        if ($this->form_item->type == 'range') {
            $responses['range'] = $this->form_item->range;
        }

        $responses['score'] = $this->score;
        $responses['result'] = $this->result;
        $responses['comment'] = $this->comment;

        return $responses;
    }
}