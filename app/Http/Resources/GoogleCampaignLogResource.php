<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GoogleCampaignLogResource extends JsonResource
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
            'ip' => $this->resource->ip,
            'link_url' => $this->resource->link_url,
            'user_agent' => $this->resource->user_agent,
            'referrer' => $this->resource->referrer,
            'type' => $this->resource->type,
            'position' => $this->resource->position,
            'time' => $this->resource->created_at
        ];
    }
}
