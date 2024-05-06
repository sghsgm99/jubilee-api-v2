<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateSiteTagRequest;
use App\Http\Requests\UpdateSiteTagRequest;
use App\Http\Resources\ArticleOnlyResource;
use App\Http\Resources\ArticleResource;
use App\Models\Services\SiteTagService;
use App\Models\Site;
use App\Models\SiteTag;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class SiteTagController extends Controller
{
    public static function apiRoutes()
    {
        Route::post('sites/{site}/tag', [SiteTagController::class, 'create']);
        Route::put('sites/{site}/tag/{tag}', [SiteTagController::class, 'update']);
        Route::delete('sites/{site}/tag/{tag}', [SiteTagController::class, 'delete']);
        Route::get('tags/{sitetag}/articles', [SiteTagController::class, 'getTagArticles']);
        Route::get('sites/{site}/tags/list-option', [SiteTagController::class, 'getTagLists']);
    }

    public function getTagLists(Request $request, Site $site)
    {
        $keyword = $request->get('keyword', null);

        return ResponseService::success('Success', SiteTagService::getListOption($site->id, $keyword));
    }

    public function getTagArticles(Request $request, SiteTag $sitetag)
    {
        $articles = $sitetag->articles()
            ->paginate($request->get('per_page', 10));

        return ArticleOnlyResource::collection($articles);
    }

    public function create(CreateSiteTagRequest $request, Site $site)
    {
        $tag = SiteTagService::create(
            $site,
            $request->validated()['label']
        );

        return ResponseService::successCreate('Site Tag was created.', $tag);
    }

    public function update(UpdateSiteTagRequest $request, Site $site, SiteTag $tag)
    {
        $tag = $tag->Service()->update(
            $request->validated()['label']
        );

        return ResponseService::success('Tag updated.', $tag);
    }

    public function delete(Request $request, Site $site, SiteTag $tag)
    {
        $tag->Service()->delete();
        return ResponseService::success('Tag was archived.');
    }
}
