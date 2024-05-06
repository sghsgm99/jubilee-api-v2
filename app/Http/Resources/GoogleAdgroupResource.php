<?php

namespace App\Http\Resources;

use App\Models\GoogleAdgroup;
use Illuminate\Http\Resources\Json\JsonResource;
use Google\Ads\GoogleAds\V15\Enums\AdGroupTypeEnum\AdGroupType;
use Google\Ads\GoogleAds\V15\Enums\AdGroupStatusEnum\AdGroupStatus;

class GoogleAdgroupResource extends JsonResource
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
            'gg_adgroup_id' => $this->resource->gg_adgroup_id,
            'bid' => $this->resource->bid,
            'type' => $this->resource->type,
            'type_label' => AdGroupType::name($this->resource->type),
            'status' => $this->resource->status,
            'status_label' => AdGroupStatus::name($this->resource->status),
            'agdata' => $this->resource->data,
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
