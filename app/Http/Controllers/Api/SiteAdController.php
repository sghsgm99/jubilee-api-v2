<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleOnlyResource;
use App\Http\Resources\ArticleResource;
use App\Models\Services\SiteAdService;
use App\Models\Site;
use App\Models\SiteAd;
use App\Models\User;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class SiteAdController extends Controller
{
    public static function apiRoutes()
    {
        Route::post('sites/{site}/ads', [SiteAdController::class, 'create']);
        Route::get('sites/{site}/ads', [SiteAdController::class, 'getCollection']);
        Route::get('sites/ads/{sitead}', [SiteAdController::class, 'edit']);
        Route::delete('sites/{site}/ads/{sitead}', [SiteAdController::class, 'delete']);
        Route::put('sites/{site}/ads/{sitead}', [SiteAdController::class, 'update']);
    }

    public function create(Request $request, Site $site)
    {
        $user = User::findOrFail($request['user_id']);
        $result = SiteAdService::create(
            $site,
            $request->input('section'),
            $request->input('name'),
            $request->input('source') ?? "",
            $request->input('source_id'),
            $request->input('platform'),
            $request->input('disclosure'),
            $request->input('border'),
            $request->input('organic'),
            $request->input('min_slide') ?? "",
            $request->input('max_slide') ?? "",
            $request->input('tags') ?? ""
        );

        return ResponseService::successCreate('Site Ad was created.', $result);
    }

    public function getCollection(Request $request, Site $site)
    {
        $ads = $site->ads()->get();
        return ResponseService::success('Success', $ads);
    }

    public function edit(Request $request, SiteAd $sitead)
    {
        return ResponseService::success('Success', SiteAdService::getAd($sitead->id));
    }

    public function delete(Request $request, Site $site, SiteAd $sitead)
    {
        $sitead->Service()->delete();
        return ResponseService::success('Ad was archived.');
    }

    public function update(Request $request, Site $site, SiteAd $sitead)
    {
        $sitead = $sitead->Service()->update(
            $request->input('name'),
            $request->input('source') ?? "",
            $request->input('source_id'),
            $request->input('platform'),
            $request->input('disclosure'),
            $request->input('border'),
            $request->input('organic'),
            $request->input('min_slide') ?? "",
            $request->input('max_slide') ?? "",
            $request->input('tags') ?? ""
        );

        return ResponseService::success('Ad updated.', $sitead);
    }
}
