<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\ArticleSite;
use App\Models\User;
use App\Models\SiteCategory;
use App\Models\SiteTag;
use App\Models\SiteMenu;
use App\Models\Site;
use App\Models\Account;
use App\Models\Enums\ArticleStatusEnum;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ArticleSiteFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ArticleSite::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $status = ArticleStatusEnum::members();
        $site = Site::all()->random();
        $article = Article::all()->random();

        return [
            'article_id' => $article->id,
            'site_id' => $site->id,
            'status' => Arr::random($status)
        ];
    }
}
