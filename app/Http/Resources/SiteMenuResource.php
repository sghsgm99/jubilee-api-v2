<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\SitePage;

class SiteMenuResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->resource->id,
            'site_id' => $this->resource->site_id,
            'is_top' => $this->resource->is_top,
            'is_bottom' => $this->resource->is_bottom,
            'sort' => $this->resource->sort,
            'title' => $this->resource->title,
            'slug' => $this->resource->slug,
            'description' => $this->resource->description,
            'status' => $this->resource->status,
            'type' => $this->resource->type,
            'pages' => ($this->resource->pages) ? $this->resource->pages->map(function (SitePage $site_page) {
                return [
                    'id' => $site_page->id,
                    'title' => $site_page->title
                ];
            }) : [],
            'account_id' => $this->resource->account_id,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
            'deleted_at' => $this->resource->deleted_at
        ];
    }
}
