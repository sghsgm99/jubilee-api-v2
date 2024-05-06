<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\UserResource;
use App\Models\FacebookAd;

class CollectionAdResource extends JsonResource
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
            'id' => $this->id,
            'status' => $this->status,
            'status_label' => $this->status->getLabel(),
            'collection_id' => $this->collection_id,
            'channel_id' => $this->channel_id,
            'channel_name' => $this->channel->title,
            'ad_account_id' => $this->ad_account_id,
            'campaign_id' => $this->campaign_id,
            'campaign_name' => $this->campaign->title,
            'adset_id' => $this->adset_id,
            'adset_name' => $this->adSet->title,
            'group_id' => $this->group_id,
            'group_name' => $this->group->name,
            'ads_number' => $this->ads_number,
            'add_images' => $this->add_images ?? [],
            'add_title' => $this->add_title ?? [],
            'add_headline' => $this->add_headline ?? [],
            'add_text' => $this->add_text ?? [],
            'add_call_to_action' => $this->add_call_to_action ?? [],
            'add_url' => $this->add_url ?? [],
            'facebook_ads' => $this->facebookAds ?? [],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user_id' => $this->user->id ?? null,
            'author_name' => $this->user->full_name ?? 'No Author'
        ];
    }
}
