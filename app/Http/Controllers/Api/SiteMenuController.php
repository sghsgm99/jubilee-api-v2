<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleOnlyResource;
use App\Http\Resources\ArticleResource;
use App\Http\Resources\SiteMenuResource;
use App\Http\Resources\SitePageResource;
use App\Models\Services\SiteMenuService;
use App\Models\Site;
use App\Models\SiteMenu;
use App\Models\Enums\SiteMenuTypeEnum;
use App\Services\ResponseService;

class SiteMenuController extends Controller
{
    public static function apiRoutes()
    {
        // site menus
        Route::post('sites/{site}/menu', [SiteMenuController::class, 'create']);
        Route::put('sites/{site}/menu/sort', [SiteMenuController::class, 'sortMenu']);
        Route::put('sites/{site}/menu/{menu}', [SiteMenuController::class, 'update']);
        Route::delete('sites/{site}/menu/{menu}', [SiteMenuController::class, 'delete']);
        Route::get('menus/{sitemenu}/articles', [SiteMenuController::class, 'getMenuArticles']);
        Route::get('sites/{site}/menus', [SiteMenuController::class, 'getCollection']);
        Route::get('sites/{site}/menus/top', [SiteMenuController::class, 'getMenusTop']);
        Route::get('sites/{site}/menus/bottom', [SiteMenuController::class, 'getMenusBottom']);
        Route::get('sites/{site}/menus/list-option', [SiteMenuController::class, 'getMenuLists']);
    }

    public function getCollection(Request $request, Site $site)
    {
        $menus = $site->menus()->orderBy('sort', 'asc')->get();
        
        return SiteMenuResource::collection($menus);
    }

    public function getMenuLists(Request $request, Site $site)
    {
        $keyword = $request->get('keyword', null);

        return ResponseService::success('Success', SiteMenuService::getListOption($site->id, $keyword));
    }

    public function getMenuArticles(Request $request, SiteMenu $sitemenu)
    {
        $articles = $sitemenu->articles()
            ->paginate($request->get('per_page', 10));

        return ArticleOnlyResource::collection($articles);
    }

    public function getMenuPages(Request $request, SiteMenu $sitemenu)
    {
        $pages = $sitemenu->pages()->get();

        return SitePageResource::collection($pages);
    }

    public function getMenusTop(Request $request, Site $site)
    {
        $menus = $site->menus()->where('is_top', '=', true)->orderBy('sort', 'asc')->get();

        return ResponseService::success('Success', $menus);
    }

    public function getMenusBottom(Request $request, Site $site)
    {
        $menus = $site->menus()->where('is_bottom', '=', true)->orderBy('sort', 'asc')->get();

        return ResponseService::success('Success', $menus);
    }

    public function create(Request $request, Site $site)
    {
        $type = $request->input('type', SiteMenuTypeEnum::CATEGORY);

        $menu = SiteMenuService::create(
            $site,
            $request->input('title'),
            $request->input('sort'),
            $request->input('is_top', 0),
            $request->input('is_bottom', 0),
            $request->input('status', 1),
            $request->input('type', SiteMenuTypeEnum::CATEGORY)
        );

        if ($request['page_id'] != null)
            $menu->Service()->syncPage($request->input('page_id'), $type);

        return ResponseService::successCreate('Site Menu was created.', $menu);
    }

    public function update(Request $request, Site $site, SiteMenu $menu)
    {
        $type = $request->input('type', SiteMenuTypeEnum::CATEGORY);

        $menu = $menu->Service()->update(
            $request->input('title'),
            $request->input('sort', 1),
            $type
        );

        if ($request['page_id'] != null)
            $menu->Service()->syncPage($request->input('page_id'), $type);
        
        return ResponseService::success('Menu updated.', $menu);
    }

    public function delete(Request $request, Site $site, SiteMenu $menu)
    {
        $menu->Service()->delete();
        return ResponseService::success('Menu was archived.');
    }
    
    public function sortMenu(Request $request, Site $site)
    {
        if (!empty($request->get('menu_ids')) && is_array($request->get('menu_ids'))) {
            $menu_ids = $request->get('menu_ids');
            $cnt = 1;
            foreach ($menu_ids as $menu_id) {
                $sort_menu = SiteMenu::find($menu_id);
                $sort_menu->Service()->update(
                    $sort_menu->title,
                    $cnt,
                    $sort_menu->type
                );
                $cnt++;
            }
            $menus = SiteMenu::whereIn('id', $menu_ids)->orderBy('sort', 'asc')->get();
        }

        return ResponseService::success('Menu Sort updated.', $menus);
    }
}
