<?php

namespace App\Http\Resources;

use App\Models\ArticleScroll;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class ArticleScrollResource.
 *
 * @property ArticleScroll $resource
 */
class ArticleScrollResource extends JsonResource
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
            'article_id' => $this->resource->article_id,
            'title' => $this->resource->title,
            'description' => $this->resource->description,
            'image_description' => $this->resource->image_description,
            'external_sync_id' => $this->resource->external_sync_id,
            'external_sync_image' => $this->resource->external_sync_image,
            'external_sync_data' => $this->resource->external_sync_data,
            'order' => $this->resource->order,
            'image' => $this->resource->image->path ?? $this->resource->external_sync_image,
        ];
    }
}
