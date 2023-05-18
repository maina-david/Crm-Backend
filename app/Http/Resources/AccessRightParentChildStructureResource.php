<?php

namespace App\Http\Resources;

use App\Models\AccessRight;
use Illuminate\Http\Resources\Json\JsonResource;

class AccessRightParentChildStructureResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $parent = $this->parent_access;
        // $access_right['selected'] = $this->withoutWrapping();
        $access_right['id'] = $parent;
        $access_right['name'] = $parent;
        $access_right['checked'] = false;

        $access_right["children"] = [];
        $children = AccessRight::where("parent_access", $parent)->get();
        foreach ($children as $key => $child) {
            $access_right["children"][$key]["id"] = $child->access_name;
            $access_right["children"][$key]["name"] = $child->access_name;
            $access_right["children"][$key]['checked'] = false;
        }
        return $access_right;
    }
}