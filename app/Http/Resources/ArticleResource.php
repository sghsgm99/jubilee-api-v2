<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Article;
use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\SiteTag;

/**
 * Class ArticleResource.
 *
 * @property Article $resource
 */
class ArticleResource extends JsonResource
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
            'slug' => $this->resource->slug,
            'content' => $this->resource->content,
            'toggle_length' => $this->resource->toggle_length,
            'status' => $this->resource->status,
            'status_label' => $this->resource->status->getLabel(),
            'is_featured' => $this->resource->is_featured,
            'is_trending' => $this->resource->is_trending,
            'revision' => $this->resource->revision,
            'type' => $this->resource->type,
            'type_label' => $this->resource->type->getLabel(),
            'categories' => ($this->resource->categories) ? $this->resource->categories->map(function (SiteCategory $category) {
                return [
                    'id' => $category->id,
                    'category_id' => $category->category_id,
                    'label' => $category->label,
                    'site_id' => $category->site_id
                ];
            }) : [],
            'tags' => ($this->resource->tags) ? $this->resource->tags->map(function (SiteTag $tag) {
                return [
                    'id' => $tag->id,
                    'tag_id' => $tag->tag_id,
                    'label' => $tag->label,
                    'site_id' => $tag->site_id
                ];
            }) : [],
            'sites' => ($this->resource->sites) ? $this->resource->sites->map(function (Site $site) {
                return [
                    'id' => $site->id,
                    'name' => $site->name,
                    'url' => $site->url,
                    'platform' => $site->platform,
                    'platform_label' => $site->platform->getLabel()
                ];
            }) : [],
            'seo' => $this->resource->seo ?? null,
            'menu' => $this->resource->menus,
            'account_id' => $this->resource->account_id,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
            'deleted_at' => $this->resource->deleted_at,
            'images' => ImageResource::collection($this->images),
            'external_sync_id' => $this->resource->external_sync_id,
            'external_sync_image' => $this->resource->external_sync_image,
            'external_sync_data' => $this->resource->external_sync_data,
            'user_id' => $this->resource->user->id ?? null,
            'user' => ($this->resource->user) ? new UserResourceLite($this->resource->user) : 'No Author'
        ];
    }
}
