<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\VerifyWordpressTokenRequest;
use App\Models\Services\SiteCategoryService;
use App\Models\Services\SiteTagService;
use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\SiteTag;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class WordpressServiceController extends Controller
{
    public static function apiRoutes()
    {
        Route::get('wordpress/{site}/request-token', [WordpressServiceController::class, 'requestToken']);
        Route::get('wordpress/{site}/sync-categories', [WordpressServiceController::class, 'syncCategories']);
        Route::get('wordpress/{site}/sync-tags', [WordpressServiceController::class, 'syncTags']);
        Route::post('wordpress/{site}/authorize-token', [WordpressServiceController::class, 'authorizeToken']);
    }

    public function requestToken(Site $site)
    {
        /**
         * this is here coz we need to clean up the fields to avoid error due to restarting process
         */
        $site->api_callback = null;
        $site->api_permissions = null;
        $site->save();

        $site = $site->SiteServiceFactory()->requestToken();

        return ResponseService::success('Success', [
            'callback' => $site->api_callback,
            'permissions' => $site->api_permissions
        ]);
    }

    public function authorizeToken(VerifyWordpressTokenRequest $request, Site $site)
    {
        $site->SiteServiceFactory()->authorizeToken($request->validated()['verify_token']);

        return ResponseService::success('Success', [
            'callback' => $site->api_callback,
            'permissions' => $site->api_permissions
        ]);
    }

    public function syncCategories(Site $site)
    {
        $categories = $site->SiteServiceFactory()->getCategories();

        foreach ($categories as $category) {
            $site_category = SiteCategory::whereSiteId($site->id)
                ->whereCategoryId($category->id)
                ->first();

            if ($site_category) {
                $site_category->Service()->update($category->name);
                continue;
            }

            SiteCategoryService::create($site, $category->name, $category->id);
        }

        return ResponseService::success('Successful site categories sync', $site->categories);
    }

    public function syncTags(Site $site)
    {
        $tags = $site->SiteServiceFactory()->getTags();

        foreach ($tags as $tag) {
            $site_tag = SiteTag::whereSiteId($site->id)
                ->whereTagId($tag->id)
                ->first();

            if ($site_tag) {
                $site_tag->Service()->update($tag->name);
                continue;
            }

            SiteTagService::create($site, $tag->name, $tag->id);
        }

        return ResponseService::success('Successful site tags sync', $site->tags);
    }
}
