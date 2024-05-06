<?php

namespace App\Http\Resources;

use App\Models\Campaign;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class CampaignResource.
 *
 * @property Campaign $resource
 */
class CampaignResource extends JsonResource
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
            'description' => $this->resource->description,
            'channel_api_preferences' => $this->resource->channel_api_preferences,
            'data_preferences' => $this->resource->data_preferences,
            'status' => $this->resource->status,
            'facebook_toggle_status' => $this->resource->channel_api_preferences['facebook_status'] === 'ACTIVE',
            'status_label' => $this->resource->status->getLabel(),
            'type' => $this->resource->type,
            'primary_text' => $this->resource->primary_text,
            'headline' => $this->resource->headline,
            'ad_description' => $this->resource->ad_description,
            'display_link' => $this->resource->display_link,
            'call_to_action' => $this->resource->call_to_action,
            'ad_image' => new ImageResource($this->resource->featureImage) ?? null,
            'channel' => new ChannelResource($this->resource->channel),
            'article' => new ArticleResource($this->resource->article),
            'site' => new SiteResource($this->resource->site),
            'tags' => CampaignTagResource::collection($this->resource->tags),
            'author_id' => $this->resource->user->id,
            'author_name' => $this->resource->user->full_name,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
            'deleted_at' => $this->resource->deleted_at
        ];
    }
}
