<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ChatVolumeTrendResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $chatVolumeTrend = [
            'id' => $this->id,
            'name' => $this->name,
            'all_conversations_count' => $this->all_conversations_count,
            'open_conversations_count' => $this->open_conversations_count,
            'closed_conversations_count' => $this->closed_conversations_count
        ];

        if (isset($this->handling_time_sum) && $this->closed_conversations_count > 0) {
            $chatVolumeTrend['average_handling_time'] = gmdate('H:i:s', ($this->handling_time_sum / $this->closed_conversations_count));
        } else {
            $chatVolumeTrend['average_handling_time'] = gmdate('H:i:s', 0);
        }

        return $chatVolumeTrend;
    }
}