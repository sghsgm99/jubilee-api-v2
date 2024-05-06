<?php

namespace App\Http\Resources;

use App\Models\Enums\CollectionCreativeTypeEnum;
use Illuminate\Http\Resources\Json\JsonResource;

class CollectionGroupCreativeResource extends JsonResource
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
            'id' => $this->id,
            'group_id' => $this->group_id,
            'creative_id' => $this->creative_id,
            'type' => $this->type,
            'title' => $this->title,
            'headline' => $this->headline,
            'text' => $this->text,
            'call_to_action' => $this->call_to_action,
            'url' => $this->url,
            'image' => $this->creative->image->path ?? null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
