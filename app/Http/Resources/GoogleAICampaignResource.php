<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Google\Ads\GoogleAds\V15\Enums\AdvertisingChannelTypeEnum\AdvertisingChannelType;
use Google\Ads\GoogleAds\V15\Enums\CampaignStatusEnum\CampaignStatus;

class GoogleAICampaignResource extends JsonResource
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
            'base_url' => $this->resource->base_url,
            'final_url' => $this->resource->final_url,
            'budget' => $this->resource->budget,
            'bid' => $this->resource->bid,
            'customer_id' => $this->resource->customer_id,
            'customer' => $this->resource->customer,
            'status' => $this->resource->status,
            'author_id' => $this->resource->user->id,
            'author_name' => $this->resource->user->full_name,
            'created_at' => $this->resource->created_at,
            'deleted_at' => $this->resource->deleted_at,
            'errored_at' => $this->resource->errored_at,
            'error_message' => $this->resource->error_message
        ];
    }
}
