<?php

namespace App\Http\Resources;

use App\Models\Channel;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class ChannelResource.
 *
 * @property Channel $resource
 */
class ChannelResource extends JsonResource
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
            'account_id' => $this->resource->account_id,
            'title' => $this->resource->title,
            'content' => $this->resource->content,
            'platform' => $this->resource->platform,
            'platform_label' => $this->resource->platform->getLabel(),
            'type' => $this->resource->channelFacebook->type,
            'type_label' => $this->resource->channelFacebook->type->getLabel(),
            'status' => $this->resource->status,
            'status_label' => $this->resource->status->getLabel(),
            'creator' => $this->resource->user->full_name ?? null,
            'facebook_channel' => $this->resource->channelFacebook,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
            'deleted_at' => $this->resource->deleted_at,
            'images' => ImageResource::collection($this->resource->images),
            'user' => new UserResourceLite($this->resource->user)
        ];
    }
}
