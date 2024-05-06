<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use App\Services\ResponseService;
use App\Http\Resources\SitePageResource;
use App\Models\Services\SiteService;
use App\Models\Services\SitePageService;
use App\Models\Site;
use App\Models\User;
use App\Models\SitePage;
use App\Http\Requests\CreateSitePageRequest;
use App\Http\Requests\UpdateSitePageRequest;
use App\Http\Requests\UploadArticleImage;

class SitePageController extends Controller
{
    public static function apiRoutes()
    {
        Route::get('sites/{site}/pages', [SitePageController::class, 'getCollection']);
        Route::post('sites/pages', [SitePageController::class, 'create']);
        Route::delete('sites/pages/{sitePage}', [SitePageController::class, 'delete']);
        Route::get('sites/pages/{sitePage}', [SitePageController::class, 'get']);
        Route::put('sites/pages/{sitePage}', [SitePageController::class, 'update']);
        Route::post('sites/pages/{site}/upload-wysiwyg-images', [SitePageController::class, 'uploadContentImages']);
    }

    public function create(CreateSitePageRequest $request)
    {
        $site_page = SitePageService::create(
            $request->validated()['site_id'],
            $request->validated()['title'],
            $request->validated()['slug'],
            $request->validated()['content']
        );

        return ResponseService::successCreate('Site Page was created.', $site_page);
    }

    public function getCollection(Request $request, Site $site)
    {
        $sort = $request->input('sort', null);
        $sort_type = $request->input('sort_type', 'asc');

        $site_pages = SitePage::search($site->id, $sort, $sort_type)
            ->paginate($request->input('per_page', 10));

        return SitePageResource::collection($site_pages);
    }

    public function get(SitePage $sitePage)
    {
        return ResponseService::success('Success', new SitePageResource($sitePage));
    }

    public function update(UpdateSitePageRequest $request, SitePage $sitePage)
    {
        $sitePage = $sitePage->Service()->update(
            $request->validated()['title'],
            $request->validated()['slug'],
            $request->validated()['content']
        );

        if (isset($sitePage['error'])) {
            return ResponseService::serverError($sitePage['message']);
        }

        return ResponseService::successCreate('Site Page was updated.', new SitePageResource($sitePage));
    }

    public function delete(SitePage $sitePage)
    {
        $sitePage->Service()->forceDelete();

        return ResponseService::success('Site Page was removed.');
    }

    public function uploadContentImages(UploadArticleImage $request, Site $site)
    {
        $images = $site->Service()->uploadContentImages($request->validated()['images']);

        return ResponseService::success('Site Page content images was uploaded.', $images);
    }
}
