<?php

namespace App\Http\Resources;

use App\Models\BuilderPage;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class BuilderPageResource.
 *
 * @property BuilderPage $resource
 */
class BuilderPageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array|Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->resource->id,
            'builder_site_id' => $this->resource->builder_site_id,
            'title' => $this->resource->title,
            'slug' => $this->resource->slug,
            'html' => $this->resource->html,
            'styling' => $this->resource->styling,
            'order' => $this->resource->order,
//            'files' => $this->resource->getPhysicalFiles(),
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
