<?php

namespace App\Http\Resources;

use App\Models\GoogleAd;
use Illuminate\Http\Resources\Json\JsonResource;
use Google\Ads\GoogleAds\V15\Enums\AdTypeEnum\AdType;
use Google\Ads\GoogleAds\V15\Enums\AdGroupAdStatusEnum\AdGroupAdStatus;

class GoogleAdResource extends JsonResource
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
            'gg_ad_id' => $this->resource->gg_ad_id,
            'ad' => $this->resource->data,
            'type' => $this->resource->type,
            'type_label' => AdType::name($this->resource->type),
            'status' => $this->resource->status,
            'status_label' => AdGroupAdStatus::name($this->resource->status),
            'author_id' => $this->resource->user->id,
            'author_name' => $this->resource->user->full_name,
            'adgroup' => $this->resource->adgroup,
            'created_at' => $this->resource->created_at,
            'deleted_at' => $this->resource->deleted_at,
            'errored_at' => $this->resource->errored_at,
            'error_message' => $this->resource->error_message
        ];
    }
}
