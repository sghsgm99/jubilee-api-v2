<?php

namespace App\Models\Services;

use App\Models\Site;
use App\Models\SiteSetting;

class SiteSettingService extends ModelService
{
    /**
     * @var SiteSetting
     */
    private $site_setting;

    public function __construct(SiteSetting $site_setting)
    {
        $this->site_setting = $site_setting;
        $this->model = $site_setting; // required
    }

    /**
     * title
     * description
     * theme_id
     * about_us_blurb
     * contact_us_blurb
     * status
     */
    public static function create(
        Site $site,
        string $title,
        string $description,
        int $theme_id,
        string $about_us_blurb,
        string $contact_us_blurb,
        int $status
    )
    {
        $site_setting = new SiteSetting();

        $site_setting->title = $title;
        $site_setting->description = $description;
        $site_setting->theme_id = $theme_id;
        $site_setting->about_us_blurb = $about_us_blurb;
        $site_setting->contact_us_blurb = $contact_us_blurb;
        $site_setting->status = $status;

        $site_setting->site_id = $site->id;
        $site_setting->account_id = $site->account_id;

        $site_setting->save();

        return $site_setting;
    }

    public function update(
        string $title,
        string $description,
        int $theme_id,
        string $about_us_blurb,
        string $contact_us_blurb,
        int $status,
        int $is_index,
        string $header_tags = null,
        string $body_tags = null,
        string $footer_tags = null,
        int $style
    )
    {
        $this->site_setting->title = $title;
        $this->site_setting->description = $description;
        $this->site_setting->theme_id = $theme_id;
        $this->site_setting->about_us_blurb = $about_us_blurb;
        $this->site_setting->contact_us_blurb = $contact_us_blurb;
        $this->site_setting->header_tags = $header_tags;
        $this->site_setting->body_tags = $body_tags;
        $this->site_setting->footer_tags = $footer_tags;
        $this->site_setting->status = $status;
        $this->site_setting->is_index = $is_index;
        $this->site_setting->style = $style;
        $this->site_setting->save();

        return $this->site_setting->fresh();
    }

    public static function getListOption(int $site_id)
    {
        return SiteSetting::whereSiteId($site_id)
            ->pluck('label', 'tag_id');
    }

    public function toggleIndex()
    {
        $this->site_setting->is_index = !$this->site_setting->is_index;
        $this->site_setting->save();
        return $this->site_setting->fresh();
    }
}
