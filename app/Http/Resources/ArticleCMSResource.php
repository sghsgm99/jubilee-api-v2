<?php

namespace App\Http\Resources;

use App\Models\Article;
use App\Models\SiteCategory;
use App\Models\Enums\ArticleTypeEnum;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class ArticleResource.
 *
 * @property Article $resource
 */
class ArticleCMSResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $articles = [];

        if ($this->resource->type->is(ArticleTypeEnum::QUIZZES())) {
            $articles = ArticleQuizzesResource::collection($this->resource->quizzes);
        }

        if ($this->resource->type->is(ArticleTypeEnum::INFINITE_SCROLL())) {
            $articles = ArticleScrollResource::collection($this->resource->scrolls);
        }

        if ($this->resource->type->is(ArticleTypeEnum::GALLERY())) {
            $articles = ArticleGalleryResource::collection($this->resource->galleries);
        }

        $category = $this->resource->categories->where('site_id', $request->input('site')['id'])->first();

        return [
            'id' => $this->resource->id,
            'title' => $this->resource->title,
            'slug' => $this->resource->slug,
            'content' => $this->resource->content,
            'type' => $this->resource->type->getLabel(),
            'status' => $this->resource->status->getLabel(),
            'is_featured' => $this->resource->is_featured,
            'is_trending' => $this->resource->is_trending,
            'revision' => $this->resource->revision,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
            'external_sync_id' => $this->resource->external_sync_id,
            'external_sync_image' => $this->resource->external_sync_image,
            'external_sync_data' => $this->resource->external_sync_data,
            'images' => ImageResource::collection($this->resource->images),
            'menus' => count($this->resource->menus),
            'category' => $category->label ?? 'Uncategorized',
            'articles' => $articles,
            'author_name' => $this->user->full_name ?? 'No Author'
        ];
    }
}
