<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateSiteRedirectRequest;
use App\Http\Requests\DeleteMultipleSiteRequest;
use App\Http\Requests\UpdateSiteRedirectRequest;
use App\Http\Resources\SiteRedirectResource;
use App\Models\Services\SiteRedirectService;
use App\Models\Site;
use App\Models\SiteRedirect;
use App\Services\ResponseService;
use Illuminate\Support\Facades\Route;

class SiteRedirectController extends Controller
{
    public static function apiRoutes()
    {
        Route::get('site-redirects/{redirect}', [SiteRedirectController::class, 'get']);
        Route::get('site-redirects', [SiteRedirectController::class, 'getCollection']);

        Route::post('site-redirects', [SiteRedirectController::class, 'create']);
        Route::put('site-redirects/{redirect}', [SiteRedirectController::class, 'update']);
        Route::delete('site-redirects/delete', [SiteRedirectController::class, 'bulkDelete']);
        Route::delete('site-redirects/{redirect}', [SiteRedirectController::class, 'delete']);
    }

    public function getCollection()
    {
        $redirect = SiteRedirect::all();
        return ResponseService::success('Success', SiteRedirectResource::collection($redirect));
    }

    public function get(SiteRedirect $redirect)
    {
        return ResponseService::success('Success', new SiteRedirectResource($redirect));
    }

    public function create(CreateSiteRedirectRequest $request)
    {
        $redirect = SiteRedirectService::create(
            Site::findOrFail($request->validated()['site_id']),
            $request->validated()['source'],
            $request->validated()['destination'],
            $request->validated()['code']
        );

        return ResponseService::success('Redirect was created', new SiteRedirectResource($redirect));
    }

    public function update(SiteRedirect $redirect, UpdateSiteRedirectRequest $request)
    {
        $siteRedirect = $redirect->Service()->update(
            $request->validated()['source'],
            $request->validated()['destination'],
            $request->validated()['code']
        );

        return ResponseService::success('Site Redirect updated', new SiteRedirectResource($redirect));
    }

    public function delete(SiteRedirect $redirect)
    {
        return $redirect->delete();
    }

    public function bulkDelete(DeleteMultipleSiteRequest $request)
    {
        return SiteRedirectService::bulkDelete($request->validated()['ids']);
    }
}
