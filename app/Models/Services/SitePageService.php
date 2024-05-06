<?php

namespace App\Models\Services;

use App\Models\Site;
use App\Models\SitePage;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class SitePageService extends ModelService
{
    /**
     * @var SitePage
     */
    private $site_page;

    public function __construct(SitePage $site_page)
    {
        $this->site_page = $site_page;
        $this->model = $site_page; // required
    }

    public static function create(
        int $site_id,
        string $title,
        string $slug,
        string $content
    )
    {
        $site_page = new SitePage();
        $site_page->title = $title;
        $site_page->slug = str_slug($slug);
        $site_page->content = $content;
        $site_page->site_id = $site_id;
        $site_page->account_id = auth()->user()->account_id;
        $site_page->user_id = Auth::user()->id;

        $site_page->save();

        return $site_page;
    }

    public function update(
        string $title,
        string $slug,
        string $content
    )
    {
        $this->site_page->title = $title;
        $this->site_page->slug = $slug;
        $this->site_page->content = $content;
        $this->site_page->save();

        return $this->site_page->fresh();
    }
}
