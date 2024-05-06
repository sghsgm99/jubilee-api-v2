<?php

namespace App\Http\Resources;

use App\Models\BlackList;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class BlackListResource.
 *
 * @property BlackList $resource
 */
class BlackListResource extends JsonResource
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
            'name' => $this->resource->name,
            'domain' => $this->resource->domain,
            'subdomain' => $this->resource->subdomain,
            'type' => $this->resource->type,
            'status' => $this->resource->status,
            'status_label' => $this->resource->status->getLabel(),
            'creator' => $this->resource->user->full_name ?? null,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
            'user' => new UserResourceLite($this->resource->user)
        ];
    }
}
