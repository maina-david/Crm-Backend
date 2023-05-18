<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class KnowldgeBaseStageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $knowldge_base = [
            "id" => $this->id,
            "knowledge_base_id" => $this->knowledge_base_id,
            "title" => $this->title,
            "detail" => $this->detail,
            "key_word" => $this->get_key_words($this->key_words)
        ];
        return $knowldge_base;
    }

    public function get_key_words($keywords)
    {
        $key_word_return = array();
        foreach ($keywords as $key => $keyword) {
            $key_word_return["id"] = $keyword->id;
            $key_word_return["key_word"] = $keyword->key_word;
        }
        return $key_word_return;
    }
}
