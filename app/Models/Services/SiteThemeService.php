<?php

namespace App\Models\Services;

use App\Models\Site;
use App\Models\SiteTheme;

class SiteThemeService extends ModelService
{
    /**
     * @var SiteTheme
     */
    private $site_theme;

    public function __construct(SiteTheme $site_theme)
    {
        $this->site_theme = $site_theme;
        $this->model = $site_theme; // required
    }

    public static function create(
        string $title,
        string $handle,
        string $description = null
    )
    {
        $site_theme = new SiteTheme();
        $site_theme->title = $title;
        $site_theme->description = $description;
        $site_theme->handle = $handle;
        $site_theme->status = 1;
        $site_theme->save();

        return $site_theme->fresh();
    }

    public function update(
        string $title,
        string $handle,
        bool $status,
        string $description = null
    )
    {
        $this->site_theme->title = $title;
        $this->site_theme->handle = $handle;
        $this->site_theme->status = $status;
        $this->site_theme->description = $description;
        $this->site_theme->save();

        return $this->site_theme;
    }

    public static function getListOption(int $site_id)
    {
        return SiteTheme::whereSiteId($site_id)
            ->pluck('label', 'tag_id');
    }

    public function toggleThemeStatus()
    {
        $this->site_theme->status = !$this->site_theme->status;
        $this->site_theme->save();
        return $this->site_theme;
    }
}
