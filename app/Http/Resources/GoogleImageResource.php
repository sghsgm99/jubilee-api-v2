<?php

namespace App\Http\Resources;

use App\Http\Resources\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class GoogleImageResource extends JsonResource
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
            'id' => $this->resource->id,
            'data' => $this->resource->data,
            'type' => $this->resource->type,
            'author_id' => $this->resource->user->id,
            'author_name' => $this->resource->user->full_name,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
            'image' => $this->resource->image->path ?? null,
            'image_name' => $this->resource->image->name ?? null,
            'image_size' => $this->resource->image->size ?? null
        ];
    }
}
