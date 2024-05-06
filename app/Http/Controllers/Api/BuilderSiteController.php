<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateBuilderSiteRequest;
use App\Http\Requests\UpdateBuilderSiteRequest;
use App\Http\Requests\UpdateBuilderSiteSettingsRequest;
use App\Http\Requests\UploadBuilderSiteImageRequest;
use App\Http\Resources\BuilderSiteResource;
use App\Models\BuilderSite;
use App\Models\Services\BuilderSiteService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class BuilderSiteController extends Controller
{
    public static function apiRoutes()
    {
        Route::post('builders', [BuilderSiteController::class, 'create']);
//        Route::post('builders/{builderSite}/generate-pages', [BuilderSiteController::class, 'generatePages']);
        Route::post('builders/{builderSite}/logo', [BuilderSiteController::class, 'uploadLogo']);
        Route::post('builders/{builderSite}/favicon', [BuilderSiteController::class, 'uploadFavicon']);
        Route::post('builders/{builderSite}/deploy', [BuilderSiteController::class, 'deploy']);
        Route::put('builders/{builderSite}/settings', [BuilderSiteController::class, 'updateSettings']);
        Route::put('builders/{builderSite}/generate-token', [BuilderSiteController::class, 'generateToken']);
        Route::put('builders/{builderSite}', [BuilderSiteController::class, 'update']);
        Route::delete('builders/{builderSite}', [BuilderSiteController::class, 'delete']);
        Route::get('builders/{builderSite}', [BuilderSiteController::class, 'get']);
        Route::get('builders', [BuilderSiteController::class, 'collection']);
    }

    public function collection(Request $request)
    {
        $search = $request->input('search', null);

        return BuilderSiteResource::collection(
            BuilderSite::search($search)
                ->latest()
                ->paginate($request->input('per_page', 10))
        );
    }

    public function get(BuilderSite $builderSite)
    {
        return new BuilderSiteResource($builderSite);
    }

    public function create(CreateBuilderSiteRequest $request)
    {
        $builder_site = BuilderSiteService::create(
            $request->validated()['name'],
            $request->validated()['domain'],
            $request->validated()['seo'] ?? null,
        );

        return ResponseService::success(
            'Site was created',
            new BuilderSiteResource($builder_site)
        );
    }

    public function generatePages(BuilderSite $builderSite)
    {
        $files = [];
        foreach ($builderSite->pages->all() as $page) {
            $files[] = $page->Service()->generateHtmlAndStylingFiles();
        }

        return ResponseService::success('Site pages was generated', $files);
    }

    public function uploadLogo(UploadBuilderSiteImageRequest $request, BuilderSite $builderSite)
    {
        $logo = $builderSite->Service()->uploadLogoOrFavicon($request->file('image'));

        return ResponseService::success('Site logo was uploaded.', $logo);
    }

    public function uploadFavicon(UploadBuilderSiteImageRequest $request, BuilderSite $builderSite)
    {
        $favicon = $builderSite->Service()->uploadLogoOrFavicon($request->file('image'), 'favicon');

        return ResponseService::success('Site favicon was uploaded.', $favicon);
    }

    public function deploy(Request $request, BuilderSite $builderSite)
    {
        $response = $builderSite->Service()->deployed($request->input('type', 'deploy'));

        return ResponseService::success('Site deployed. It will take at least 3-5 minutes to propagate.', $response);
    }

    public function update(UpdateBuilderSiteRequest $request, BuilderSite $builderSite)
    {
        $builder_site = $builderSite->Service()->update(
            $request->validated()['name'],
            $request->validated()['domain'],
            $request->validated()['seo'] ?? null,
            $request->validated()['preview_link'] ?? null
        );

        return ResponseService::success(
            'Site was updated',
            new BuilderSiteResource($builder_site)
        );
    }

    public function updateSettings(UpdateBuilderSiteSettingsRequest $request, BuilderSite $builderSite)
    {
        $builder_site = $builderSite->Service()->updateSettings(
            $request->validated()['host'],
            $request->validated()['ssh_username'],
            $request->validated()['ssh_password'],
            $request->validated()['path']
        );

        return ResponseService::success(
            'Site settings was updated',
            new BuilderSiteResource($builder_site)
        );
    }

    public function generateToken(BuilderSite $builderSite)
    {
        return ResponseService::success(
          'Site token was generated',
          $builderSite->Service()->generateToken()
        );
    }

    public function delete(BuilderSite $builderSite)
    {
        $builderSite->Service()->delete();

        return ResponseService::success('Site was archived');
    }
}
