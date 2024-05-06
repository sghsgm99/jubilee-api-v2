<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\ArticleDuplicateRequest;
use App\Http\Requests\AttachArticleToSiteRequest;
use App\Http\Requests\UploadArticleImage;
use App\Http\Resources\ArticleOnlyResource;
use App\Http\Resources\ArticleTrashedResource;
use App\Http\Resources\ImageResource;
use App\Http\Resources\SiteOnlyResource;
use App\Http\Resources\SiteResource;
use App\Models\Enums\ArticleStatusEnum;
use App\Models\Enums\SitePlatformEnum;
use App\Models\Site;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleResource;
use App\Models\Services\ArticleService;
use App\Models\Article;
use App\Models\User;
use App\Http\Requests\CreateArticleRequest;
use App\Http\Requests\DeleteMultipleArticleRequest;
use App\Http\Requests\UpdateArticleRequest;
use App\Http\Resources\ArticleGlobalSearchResource;
use App\Models\Enums\ArticleTypeEnum;
use App\Services\ResponseService;

use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public static function apiRoutes()
    {
        Route::post('articles', [ArticleController::class, 'create']);
        Route::get('articles/global-search/{search}', [ArticleController::class, 'globalSearch']);
        Route::post('articles/{article}/duplicate', [ArticleController::class, 'duplicate']);
        Route::post('articles/{article}/attach-site', [ArticleController::class, 'attachSite']);
        Route::post('articles/{article}/upload-image', [ArticleController::class, 'uploadImage']);
        Route::post('articles/{article}/upload-wysiwyg-images', [ArticleController::class, 'uploadContentImages']);
        Route::post('articles/{article}/update-seo', [ArticleController::class, 'updateSeo']);
        Route::post('articles/{article}/restore', [ArticleController::class, 'restore']);
        Route::post('articles/{article}/published', [ArticleController::class, 'published']);
        Route::put('articles/{article}/update-featured', [ArticleController::class, 'updateFeatured']);
        Route::put('articles/{article}/update-trending', [ArticleController::class, 'updateTrending']);
        Route::put('articles/{article}/featured-image/{image_id}', [ArticleController::class, 'setFeaturedImage']);
        Route::put('articles/{article}', [ArticleController::class, 'update']);
        Route::delete('articles/delete', [ArticleController::class, 'deleteMultiple']);
        Route::delete('articles/{article}/detach-site/{site}', [ArticleController::class, 'detachSite']);
        Route::delete('articles/{article}/image/{image_id}', [ArticleController::class, 'deleteImage']);
        Route::delete('articles/{article}/wysiwyg-image/{filename}', [ArticleController::class, 'deleteContentImage']);
        Route::delete('articles/{article}/history/{history_id}', [ArticleController::class, 'deleteHistory']);
        Route::delete('articles/{article}', [ArticleController::class, 'delete']);
        Route::get('articles/archive', [ArticleController::class, 'getTrash']);
        Route::get('articles/list-option', [ArticleController::class, 'getArticleListOptions']);
        Route::get('articles/{article}/wysiwyg-images', [ArticleController::class, 'getContentImages']);
        Route::get('articles/{article}/history', [ArticleController::class, 'getHistory']);
        Route::get('articles/{article}/sites/{site}', [ArticleController::class, 'getArticleAndSite']);
        Route::get('articles/{article}/sites', [ArticleController::class, 'getArticleSites']);
        Route::get('articles/{article}', [ArticleController::class, 'get']);
        Route::get('articles', [ArticleController::class, 'getCollection']);
    }

    public function getCollection(Request $request)
    {
        $search = $request->input('search', null);
        $status = ArticleStatusEnum::memberByValue($request->input('status', null));
        $sort = $request->input('sort', null);
        $sort_type = $request->input('sort_type', 'asc');
        $type = ArticleTypeEnum::memberByValue($request->input('type', null));
        $owner = $request->input('owner', null);

        $articles = Article::search($search, $status, $sort, $sort_type, $type, $owner)
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 10));

        return ArticleResource::collection($articles);
    }

    public function globalSearch($search)
    {
        return ArticleGlobalSearchResource::collection(ArticleService::gloabalSearch($search));
    }

    public function getTrash(Request $request)
    {
        return ArticleTrashedResource::collection(
            Article::onlyTrashed()
                ->orderBy('deleted_at', 'desc')
                ->paginate($request->input('per_page', 10))
        );
    }

    public function getArticleListOptions(Request $request)
    {
        $keyword = $request->get('keyword', null);

        $query = Article::wherePublished();

        if ($keyword) {
            $query->where('title', 'LIKE', "%{$keyword}%");
        }

        $sites = $query->get(['id', 'title', 'slug'])->toArray();

        return ResponseService::success('Success', $sites);
    }

    public function get(Article $article)
    {
        return ResponseService::success('Success', new ArticleResource($article));
    }

    public function getHistory(Article $article)
    {
        return ResponseService::success('Success', $article->articleHistory);
    }

    public function getArticleAndSite(Article $article, Site $site)
    {
        $article = $site->articles()
            ->where('article_id', $article->id)
            ->where('site_id', $site->id)
            ->first();

        return ResponseService::success('Success', [
            'article' => new ArticleOnlyResource($article),
            'site' => new SiteOnlyResource($site)
        ]);
    }

    public function getArticleSites(Request $request, Article $article)
    {
        $sort = $request->input('sort', null);
        $sort_type = $request->input('sort_type', 'asc');

        return SiteResource::collection(
            $article->sites($sort, $sort_type)->paginate($request->input('per_page', 10))
        );
    }

    public function create(Request $request)
    {
        $article = ArticleService::create(
            auth()->user(),
            ArticleStatusEnum::DRAFT(),
            $request->type ? ArticleTypeEnum::memberByValue($request->type) : ArticleTypeEnum::POST(),
            'Untitled',
            null,
            null,
            []
        );

        return ResponseService::successCreate('Article was created.', new ArticleResource($article));
    }

    public function duplicate(ArticleDuplicateRequest $request, Article $article)
    {
        $newArticle = $article->Service()->duplicate(
            ArticleTypeEnum::memberByValue($request->validated()['type'])
        );

        return ResponseService::successCreate(
            'Article was duplicated.',
            new ArticleResource($newArticle)
        );
    }

    public function attachSite(AttachArticleToSiteRequest $request, Article $article)
    {
        $site = Site::whereId($request->validated()['site_id'])->firstOrFail();

        $article->sites()->syncWithPivotValues([$site->id], [
            'status' => ArticleStatusEnum::memberByValue($request->validated()['status']),
        ], false);

        $article->Service()->syncCategories($request->validated()['category_ids']);

        $article->Service()->syncTags($request->validated()['tag_ids']);

        $article->Service()->syncMenus($request->validated()['menu_ids']);

        if ($site->is_wordpress_integrated) {
            $article_site = $site->SiteServiceFactory()->getArticleSite($article->id);

            if ($article_site && $article_site->external_post_id === null) {
                $site->SiteServiceFactory()->createPost($article);
            }

            if ($article_site && $article_site->external_post_id !== null) {
                $site->SiteServiceFactory()->updatePost($article);
            }
        }

        return ResponseService::success('Article was attached to site.');
    }

    public function detachSite(Article $article, Site $site)
    {
        if ($site->is_wordpress_integrated) {
            $site->SiteServiceFactory()->deletePost($article);
        }

        $article->categories()->detach($site->categories()->pluck('id'));

        $article->tags()->detach($site->tags()->pluck('id'));

        $article->menus()->detach($site->menus()->pluck('id'));

        $article->sites()->detach($site->id);

        if ($site->platform->is(SitePlatformEnum::JUBILEE())) {
            // TODO we don't have this feature yet
//            $article->Service()->published();
        }

        return ResponseService::success('Article was remove from the site.');
    }

    public function update(UpdateArticleRequest $request, Article $article)
    {
        $user = User::where('id', $request->validated()['user_id'])->first();

        $article->Service()->update(
            $user,
            $request->validated()['title'],
            $request->validated()['slug'],
            $request->validated()['content'],
            $request->validated()['toggle_length'],
            ArticleStatusEnum::memberByValue($request->validated()['status'])
        );

        return ResponseService::successCreate('Article was updated.', new ArticleResource($article));
    }

    public function updateSeo(Request $request, Article $article)
    {
        $article->Service()->updateSeo(
            $request->title,
            $request->keyword,
            $request->description,
            $request->tags
        );
        return ResponseService::successCreate('SEO was updated.', new ArticleResource($article));
    }

    public function uploadImage(UploadArticleImage $request, Article $article)
    {
        $images = $article->Service()->uploadImages($request->validated()['images']);

        return ResponseService::success('Article images was uploaded.', ImageResource::collection($images));
    }

    public function uploadContentImages(UploadArticleImage $request, Article $article)
    {
        $images = $article->Service()->uploadContentImages($request->validated()['images']);

        return ResponseService::success('Article content images was uploaded.', $images);
    }

    public function getContentImages(Article $article)
    {
        $images = $article->Service()->getFilesByDirectory(
            $article->getContentImagesDir()
        );

        return ResponseService::success('Success.', $images);
    }

    public function setFeaturedImage(Article $article, int $image_id)
    {
        $article->Service()->markAsFeatured($image_id);

        return ResponseService::success('Featured image was set.');
    }

    public function restore($id)
    {
        $article = Article::whereId($id)->withTrashed()->first();
        $article->restore();

        $article->status = ArticleStatusEnum::DRAFT;
        $article->save();

        return ResponseService::success('Article restored.');
    }

    public function published(Article $article)
    {
        return ResponseService::serviceUnavailable();
        // $article->Service()->published();

        // return ResponseService::success('Article published. It will take at least 3-5 minutes to propagate.');
    }

    public function deleteHistory(Article $article, int $history_id)
    {
        $articleHistory = $article->articleHistory()->findOrFail($history_id);

        $articleHistory->Service()->delete();

        return ResponseService::success('Article history was archived.');
    }

    public function delete(Article $article)
    {
        if (!$article->Service()->delete()) {
            return ResponseService::serverError('Article cannot be deleted because of a relationship data with Campaigns');
        }

        return ResponseService::success('Article was archived.');
    }

    public function deleteMultiple(DeleteMultipleArticleRequest $request)
    {
        return ArticleService::BulkDelete($request->validated()['ids']);
    }

    public function deleteImage(Article $article, int $image_id)
    {
        $article->Service()->detachImage($image_id);

        return ResponseService::success('Article image was deleted.');
    }

    public function deleteContentImage(Article $article, string $filename)
    {
        $article->Service()->deleteContentImage($filename, $article->getContentImagesDir());

        return ResponseService::success('Article content image was deleted.');
    }

    public function updateFeatured(Article $article)
    {
        return ResponseService::success('Success', new ArticleResource($article->Service()->updateFeatured()));
    }

    public function updateTrending(Article $article)
    {
        return ResponseService::success('Success', new ArticleResource($article->Service()->updateTrending()));
    }
}
