<?php

namespace App\Http\Resources;

use App\Models\SitePage;
use Illuminate\Http\Resources\Json\JsonResource;

class SitePageResource extends JsonResource
{
    public function toArray($request)
    {
        return[
            'id' => $this->resource->id,
            'title' => $this->resource->title,
            'slug' => $this->resource->slug,
            'content' => $this->resource->content,
            'user' => new UserResourceLite($this->resource->user),
            'account_id' => $this->resource->account_id,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
            'deleted_at' => $this->resource->deleted_at
        ];
    }
}
