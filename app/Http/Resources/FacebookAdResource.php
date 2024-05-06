<?php

namespace App\Http\Resources;

use App\Models\FacebookAd;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class FacebookAdResource.
 *
 * @property FacebookAd $resource
 */
class FacebookAdResource extends JsonResource
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
            'adset_id' => $this->resource->adset_id,
            'fb_ad_id' => $this->resource->fb_ad_id,
            'article_id' => $this->resource->article_id,
            'site_id' => $this->resource->site_id ?? null,
            'page_name' => $this->resource->campaign_channel->title ?? null,
            'type' => $this->resource->type,
            'title' => $this->resource->title_format,
            'primary_text' => $this->resource->primary_text,
            'headline' => $this->resource->headline,
            'description' => $this->resource->description,
            'display_link' => $this->resource->display_link,
            'url' => $this->resource->link_format,
            'status' => $this->resource->status,
            'status_label' => $this->resource->status->getLabel(),
            'fb_status' => $this->resource->fb_status,
            'fb_status_label' => $this->resource->fb_status->getLabel(),
            'call_to_action' => $this->resource->call_to_action ?? null,
            'call_to_action_label' => $this->resource->call_to_action->getLabel() ?? null,
            'image' => $this->resource->featured_image->path ?? null,
            'author_id' => $this->resource->user_id,
            'author_name' => $this->resource->user->full_name,
            'created_at' => $this->resource->created_at,
            'errored_at' => $this->resource->errored_at,
            'error_message' => $this->resource->error_message
        ];
    }
}
