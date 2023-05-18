<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TeamPerfomanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $team = [
            'id' => $this->id,
            'name' => $this->name,
        ];

        if ($request->has('from') && $request->has('to')) {
            $team['total_reviews'] = $this->evaluations->where('created_at', '>=', $request->get('from') . ' 00.00.00')
                ->where('created_at', '<=', $request->get('to') . ' 23.59.59')
                ->count();
            $team['agents_reviewed'] = $this->evaluations->where('created_at', '>=', $request->get('from') . ' 00.00.00')
                ->where('created_at', '<=', $request->get('to') . ' 23.59.59')
                ->groupBy('agent_id')
                ->count();
            $averageHandlingTime = $this->evaluations->where('created_at', '>=', $request->get('from') . ' 00.00.00')
                ->where('created_at', '<=', $request->get('to') . ' 23.59.59')
                ->avg('handling_time');
            $team['average_handling_time'] = (isset($averageHandlingTime) ? gmdate('H:i:s', $averageHandlingTime) : gmdate('H:i:s', 0));
        } else {
            $team['total_reviews'] = $this->evaluations->count();
            $team['agents_reviewed'] = $this->evaluations->groupBy('agent_id')->count();
            $averageHandlingTime = $this->evaluations->avg('handling_time');
            $team['average_handling_time'] = (isset($averageHandlingTime) ? gmdate('H:i:s', $averageHandlingTime) : gmdate('H:i:s', 0));
        }

        return $team;
    }
}