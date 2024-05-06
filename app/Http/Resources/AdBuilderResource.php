<?php

namespace App\Http\Resources;

use App\Models\AdBuilder;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class AdBuilderResource.
 *
 * @property AdBuilder $resource
 */
class AdBuilderResource extends JsonResource
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
            'name' => $this->resource->name,
            'url' => $this->resource->url,
            'gjs_components' => $this->resource->gjs_components,
            'gjs_style' => $this->resource->gjs_style,
            'gjs_html' => $this->resource->gjs_html,
            'gjs_css' => $this->resource->gjs_css,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at
        ];
    }
}
