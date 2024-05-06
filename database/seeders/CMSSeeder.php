<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Article;
use App\Models\Enums\ArticleStatusEnum;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Seeder;
use App\Models\SiteMenu;
use App\Models\SiteCategory;
use App\Models\SiteTag;
use App\Models\SiteTheme;
use App\Models\SiteSetting;
use App\Models\ArticleSite;

class CMSSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        SiteTheme::factory(SiteTheme::class)->count(1)->create();

        /**
         * Site Menus
         */
        $sites = Site::whereAccountId(1)->get();
        foreach ($sites as $site) {
            SiteMenu::factory(SiteMenu::class)
                ->for($site)
                ->for($site->account)
                ->count(rand(2, 6))
                ->create();
        }

        $sites = Site::whereAccountId(2)->get();
        foreach ($sites as $site) {
            SiteMenu::factory(SiteMenu::class)
                ->for($site)
                ->for($site->account)
                ->count(rand(2, 4))
                ->create();
        }

        /**
         * Site Categories
         */
        $sites = Site::whereAccountId(1)->get();
        foreach ($sites as $site) {
            SiteCategory::factory(SiteCategory::class)
                ->for($site)
                ->for($site->account)
                ->count(rand(2, 5))
                ->create([
                    'category_id' => null
                ]);
        }

        $sites = Site::whereAccountId(2)->get();
        foreach ($sites as $site) {
            SiteCategory::factory(SiteCategory::class)
                ->for($site)
                ->for($site->account)
                ->count(rand(2, 3))
                ->create([
                    'category_id' => null
                ]);
        }

        /**
         * Site Tags
         */
        $sites = Site::whereAccountId(1)->get();
        foreach ($sites as $site) {
            SiteTag::factory(SiteTag::class)
                ->for($site)
                ->for($site->account)
                ->count(rand(3, 6))
                ->create([
                    'tag_id' => null
                ]);
        }

        $sites = Site::whereAccountId(2)->get();
        foreach ($sites as $site) {
            SiteTag::factory(SiteTag::class)
                ->for($site)
                ->for($site->account)
                ->count(rand(1, 3))
                ->create([
                    'tag_id' => null
                ]);
        }

        /**
         * Site Settings
         */
        $sites = Site::whereAccountId(1)->get();
        $site_theme = SiteTheme::all()->first();
        foreach ($sites as $site) {
            SiteSetting::factory(SiteSetting::class)
                ->for($site)
                ->for($site->account)
                ->for($site_theme)
                ->count(1)
                ->create();
        }

        $sites = Site::whereAccountId(2)->get();
        foreach ($sites as $site) {
            SiteSetting::factory(SiteSetting::class)
                ->for($site)
                ->for($site->account)
                ->for($site_theme)
                ->count(1)
                ->create();
        }

        /**
         * Attached article to a site
         */
        $articles = Article::whereAccountId(1)->get();
        $articleStatusEnums = ArticleStatusEnum::members();
        foreach ($articles as $article) {
            $site = Site::whereAccountId($article->account_id)->get()->random();

            $article->sites()->syncWithPivotValues([$site->id], [
                'status' => array_random($articleStatusEnums),
            ]);

            $category_ids = SiteCategory::whereSiteId($site->id)->pluck('id');
            $article->categories()->sync($category_ids);

            $tag_ids = SiteTag::whereSiteId($site->id)->pluck('id');
            $article->tags()->sync($tag_ids);

            $menu_ids = SiteMenu::whereSiteId($site->id)->pluck('id');
            $article->menus()->sync($menu_ids);
        }

        $articles = Article::whereAccountId(2)->get();
        foreach ($articles as $article) {
            $site = Site::whereAccountId($article->account_id)->get()->random();

            $article->sites()->syncWithPivotValues([$site->id], [
                'status' => array_random($articleStatusEnums),
            ]);

            $category_ids = SiteCategory::whereSiteId($site->id)->pluck('id');
            $article->categories()->sync($category_ids);

            $tag_ids = SiteTag::whereSiteId($site->id)->pluck('id');
            $article->tags()->sync($tag_ids);

            $menu_ids = SiteMenu::whereSiteId($site->id)->pluck('id');
            $article->menus()->sync($menu_ids);
        }
    }
}
