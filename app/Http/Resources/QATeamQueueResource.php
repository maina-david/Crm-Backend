<?php

namespace App\Http\Resources;

use App\Models\ChatQueue;
use App\Models\Queue;
use Illuminate\Http\Resources\Json\JsonResource;

class QATeamQueueResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $queue = [
            'id' => $this->queue_id,
            'type' => $this->queue_type
        ];

        if ($this->queue_type == 'chat') {
            $queue['name'] = ChatQueue::find($this->queue_id)->name;
        } else {
            $queue['name'] = Queue::find($this->queue_id)->name;
        }

        return $queue;
    }
}