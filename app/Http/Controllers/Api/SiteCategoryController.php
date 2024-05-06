<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateSiteCategoryRequest;
use App\Http\Requests\UpdateSiteCategoryRequest;
use App\Http\Resources\ArticleOnlyResource;
use App\Http\Resources\ArticleResource;
use App\Models\Services\SiteCategoryService;
use App\Models\Site;
use App\Models\SiteCategory;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class SiteCategoryController extends Controller
{
    public static function apiRoutes()
    {
        Route::post('sites/{site}/category', [SiteCategoryController::class, 'create']);
        Route::put('sites/{site}/category/{category}', [SiteCategoryController::class, 'update']);
        Route::delete('sites/{site}/category/{category}', [SiteCategoryController::class, 'delete']);
        Route::get('categories/{sitecategory}/articles', [SiteCategoryController::class, 'getCategoryArticles']);
        Route::get('sites/{site}/categories/list-option', [SiteCategoryController::class, 'getCategoryLists']);
    }

    public function getCategoryLists(Request $request, Site $site)
    {
        $keyword = $request->get('keyword', null);

        return ResponseService::success('Success', SiteCategoryService::getListOption($site->id, $keyword));
    }

    public function getCategoryArticles(Request $request, SiteCategory $sitecategory)
    {
        $articles = $sitecategory->articles()
            ->paginate($request->get('per_page', 10));

        return ArticleOnlyResource::collection($articles);
    }

    public function create(CreateSiteCategoryRequest $request, Site $site)
    {
        $category = SiteCategoryService::create(
            $site,
            $request->validated()['label']
        );

        return ResponseService::successCreate('Site Category was created.', $category);
    }

    public function update(UpdateSiteCategoryRequest $request, Site $site, SiteCategory $category)
    {
        $category = $category->Service()->update(
            $request->validated()['label']
        );

        return ResponseService::success('Category updated.', $category);
    }

    public function delete(Site $site, SiteCategory $category)
    {
        $category->Service()->delete();
        return ResponseService::success('Category was archived.');
    }
}
