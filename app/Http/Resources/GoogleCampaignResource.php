<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\GoogleCampaign;
use Google\Ads\GoogleAds\V15\Enums\AdvertisingChannelTypeEnum\AdvertisingChannelType;
use Google\Ads\GoogleAds\V15\Enums\CampaignStatusEnum\CampaignStatus;

class GoogleCampaignResource extends JsonResource
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
            'gg_campaign_id' => $this->resource->gg_campaign_id,
            'title' => $this->resource->title,
            'description' => $this->resource->description,
            'customer_id' => $this->resource->customer_id,
            'customer' => $this->resource->customer,
            'budget' => $this->resource->budget,
            'location' => $this->resource->location,
            'type' => $this->resource->type,
            'type_label' => AdvertisingChannelType::name($this->resource->type),
            'status' => $this->resource->status,
            'status_label' => CampaignStatus::name($this->resource->status),
            'cdata' => $this->resource->data,
            'author_id' => $this->resource->user->id,
            'author_name' => $this->resource->user->full_name,
            'created_at' => $this->resource->created_at,
            'deleted_at' => $this->resource->deleted_at,
            'errored_at' => $this->resource->errored_at,
            'error_message' => $this->resource->error_message
        ];
    }
}
