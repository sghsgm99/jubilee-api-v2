<?php

namespace App\Http\Resources;

use App\Models\FacebookAdset;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class FacebookAdsetResource.
 *
 * @property FacebookAdset $resource
 */
class FacebookAdsetResource extends JsonResource
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
            'title' => $this->resource->title,
            'fb_adset_id' => $this->resource->fb_adset_id,
            'adset' => $this->resource->data,
            'status' => $this->resource->status,
            'status_label' => $this->resource->status->getLabel(),
            'fb_status' => $this->resource->fb_status,
            'fb_status_label' => $this->resource->fb_status->getLabel(),
            'author_id' => $this->resource->user->id,
            'author_name' => $this->resource->user->full_name,
            'campaign' => $this->resource->campaign,
            'created_at' => $this->resource->created_at,
            'deleted_at' => $this->resource->deleted_at,
            'errored_at' => $this->resource->errored_at,
            'error_message' => $this->resource->error_message
        ];
    }
}
