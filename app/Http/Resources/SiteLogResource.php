<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\SiteLog;

class SiteLogResource extends JsonResource
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
            'site' => $this->resource->site->url,
            'ip' => $this->resource->ip,
            'type' => $this->resource->type,
            'position' => $this->resource->position,
            'created_at' => $this->resource->created_at
        ];
    }
}
