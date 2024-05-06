<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleResource;
use App\Http\Resources\ArticleCMSResource;
use App\Http\Resources\ArticleOnlyResource;
use App\Models\Article;
use App\Models\AIArticle;
use App\Models\Site;
use App\Models\SiteMenu;
use App\Models\SiteTag;
use App\Models\SiteCategory;
use App\Models\SiteTheme;
use App\Models\SiteSetting;
use App\Models\Services\SiteCategoryService;
use App\Models\Services\SiteMenuService;
use App\Models\Services\SiteTagService;
use App\Models\Services\SiteThemeService;
use App\Models\Services\SiteSettingService;
use App\Models\Services\ArticleService;
use App\Models\Services\AIArticleService;
use App\Services\OpenAIService;

class CMSSiteController extends Controller
{
    public static function apiRoutes()
    {
        // Route::get('cms/{site}/articles', [CMSSiteController::class, 'getArticles']);
        // Route::get('cms/{site}/categories', [CMSSiteController::class, 'getCategories']);
        // Route::get('cms/{site}/theme', [CMSSiteController::class, 'getTheme']);
        // Route::get('cms/{site}/menu', [CMSSiteController::class, 'getMenu']);
        // Route::get('cms/{site}/tags', [CMSSiteController::class, 'getTags']);
        // Route::get('cms/{site}/settings', [CMSSiteController::class, 'getSettings']);
    }

    public static function webhooks()
    {
        Route::get('cms/articles', [CMSSiteController::class, 'getArticles']);
        Route::get('cms/article/{article}', [CMSSiteController::class, 'getArticle']);
        Route::get('cms/categories', [CMSSiteController::class, 'getCategories']);
        Route::get('cms/categories/{sitecategory}/articles', [CMSSiteController::class, 'getCategoryArticles']);
        Route::get('cms/theme', [CMSSiteController::class, 'getTheme']);
        Route::get('cms/tags', [CMSSiteController::class, 'getTags']);
        Route::get('cms/settings', [CMSSiteController::class, 'getSettings']);
        Route::get('cms/menus', [CMSSiteController::class, 'getMenus']);
        Route::get('cms/menus/{sitemenu}/articles', [SiteMenuController::class, 'getMenuArticles']);
        Route::get('cms/menus/{sitemenu}/pages', [SiteMenuController::class, 'getMenuPages']);
        Route::get('cms/menu/top', [CMSSiteController::class, 'getMenuTop']);
        Route::get('cms/menu/bottom', [CMSSiteController::class, 'getMenuBottom']);
        Route::get('cms/menu/page/bottom', [CMSSiteController::class, 'getMenuPageBottom']);
        Route::get('cms/menu/{menu}/articles', [CMSSiteController::class, 'getMenuArticles']);
        Route::get('cms/ai-articles', [CMSSiteController::class, 'getAIArticles']);
    }

    /**
     * Service Get Article
     * @param Request $request
     */
    public function getArticles(Request $request)
    {
        $site = $request->input('site');
        $search = $request->input('search', '');

        return ArticleCMSResource::collection(
            $site->articles($search)->paginate($request->input('per_page', 10))
        );
    }

    /**
     * Service Get Article
     * @param Request $request
     */
    public function getArticle($article, Request $request)
    {
        $article = Article::findOrFail($article);
        return new ArticleCMSResource($article);
    }

    /**
     * Service Get Theme
     * @param Request $request
     */
    public function getTheme(Request $request)
    {
        $site = $request->input('site');
        return $site->settings()->first()->theme()->first();
    }

    /**
     * Service Get Menu
     * @param Request $request
     */
    public function getMenus(Request $request)
    {
        $site = $request->input('site');
        return $site->menus()->get();
    }

    /**
     * Service Get Menu
     * @param Request $request
     */
    public function getMenuTop(Request $request)
    {
        $site = $request->input('site');
        return $site->menus()->where('is_top', true)->get();
    }

    /**
     * Service Get Menu
     * @param Request $request
     */
    public function getMenuBottom(Request $request)
    {
        $site = $request->input('site');
        return $site->menus()->where('is_bottom', true)->get();
    }

    public function getMenuPageBottom(Request $request)
    {
        $site = $request->input('site');
        return $site->menus()->where('is_bottom', true)->where('type', 2)->get();
    }

    /**
     * Service Get Menu Articles
     * @param Request $request
     */
    public function getMenuArticles(Request $request, SiteMenu $menu)
    {
        $site = $request->input('site');
        return $site->menus()->where('is_bottom', true)->get();
    }

    /**
     * Service Get Settings
     * @param Request $request
     */
    public function getSettings(Request $request)
    {
        $site = $request->input('site');

        $settings = $site->settings()->first();

        $settings->analytic_script = $site->analytic_script ?? null;
        $settings->favicon = $site->favicon->favicon_image ?? null;
        $settings->logo = $site->logo->logo_image ?? null;
        $settings->analytic_id = $site->view_id ?? null;

        return $settings;
    }

    /**
     * Service Get Categories
     * @param Request $request
     */
    public function getCategories(Request $request)
    {
        $site = $request->input('site');
        return $site->categories()->get();
    }

    public function getCategoryArticles(Request $request, SiteCategory $sitecategory)
    {
        $articles = $sitecategory->articles()
            ->paginate($request->get('per_page', 10));

        return ArticleOnlyResource::collection($articles);
    }

    /**
     * Service Get Tags
     * @param Request $request
     */
    public function getTags(Request $request)
    {
        $site = $request->input('site');
        return $site->tags()->get();
    }

    public function getAIArticles(Request $request)
    {
        $keyword = $request->input('search');
        $result = AIArticle::search($keyword)->get();

        if (count($result) > 0)
            return $result;

        $openAIService = OpenAIService::resolve();

        $title_prompt = "generate article title: `$keyword`";
        $title = $openAIService->generateAIText($title_prompt, 50);

        $content_prompt = "generate article content titled `$title`";
        $content = $openAIService->generateAIText($content_prompt, 500);

        $article = AIArticleService::create(
            $title,
            str_replace("\n", "<br/>", $content)
        );
        
        return [$article];
    }
}
