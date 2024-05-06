<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateBuilderPageRequest;
use App\Http\Requests\UpdateBuilderPageRequest;
use App\Http\Resources\BuilderPageResource;
use App\Models\BuilderPage;
use App\Models\BuilderSite;
use App\Models\Services\BuilderPageService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class BuilderPageController extends Controller
{
    public static function apiRoutes()
    {
        Route::post('builder-pages', [BuilderPageController::class, 'create']);
//        Route::post('builder-pages/{builderPage}/generate-page', [BuilderPageController::class, 'generatePage']);
        Route::put('builder-pages/sort', [BuilderPageController::class, 'updateSortOrder']);
        Route::put('builder-pages/{builderPage}', [BuilderPageController::class, 'update']);
        Route::delete('builder-pages/{builderPage}', [BuilderPageController::class, 'delete']);
        Route::get('builder-pages/{builderPage}/grape-details', [BuilderPageController::class, 'getGrapeDetail']);
        Route::get('builder-pages/{builderPage}', [BuilderPageController::class, 'get']);
        Route::get('builder-pages', [BuilderPageController::class, 'collection']);
    }

    public function collection(Request $request)
    {
        if (! $request->has('builder_site_id')) {
            return ResponseService::clientError('Missing builder site ID');
        }

        $builder_site_id = $request->input('builder_site_id');
        $search = $request->input('search', null);

        return BuilderPageResource::collection(
            BuilderPage::whereBuilderSiteId($builder_site_id)
                ->search($search)
                ->orderBy('order')
                ->paginate($request->input('per_page', 10))
        );
    }

    public function get(BuilderPage $builderPage)
    {
        return new BuilderPageResource($builderPage);
    }

    /**
     * Specific for GrapeJs endpoint. Don't touch this.
     *
     * @param BuilderPage $builderPage
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGrapeDetail(BuilderPage $builderPage)
    {
        return response()->json([
            'gjs-components' => null,
            'gjs-style' => null,
            'gjs-html' => $builderPage->html,
            'gjs-css' => $builderPage->styling,
        ]);
    }

    public function create(CreateBuilderPageRequest $request)
    {
        $builder_page = BuilderPageService::create(
            BuilderSite::findOrFail($request->validated()['builder_site_id']),
            $request->validated()['title'],
            $request->validated()['slug']
        );

        return ResponseService::success(
            'Page was created',
            new BuilderPageResource($builder_page)
        );
    }

    public function generatePage(BuilderPage $builderPage)
    {
        return ResponseService::success(
            'Page was generated',
            $builderPage->Service()->generateHtmlAndStylingFiles()
        );
    }

    public function update(UpdateBuilderPageRequest $request, BuilderPage $builderPage)
    {
        $builder_page = $builderPage->Service()->update(
            $request->validated()['title'],
            $request->validated()['slug'],
            $request->validated()['html'] ?? null,
            $request->validated()['styling'] ?? null,
            $request->validated()['seo'] ?? null
        );

        return ResponseService::success(
            'Page was updated',
            new BuilderPageResource($builder_page)
        );
    }

    public function updateSortOrder(Request $request)
    {
        if (empty($request->input('ids')) && !is_array($request->input('ids'))) {
            return ResponseService::clientError('Invalid data.');
        }

        foreach ($request->input('ids') as $index => $id) {
            if ($builder_page = BuilderPage::first($id)) {
                $builder_page->Service()->updateOrder($index);
            }
        }

        return ResponseService::success('Page sort order updated');
    }

    public function delete(BuilderPage $builderPage)
    {
        $builderPage->Service()->delete();

        return ResponseService::success('Page was archived');
    }
}
