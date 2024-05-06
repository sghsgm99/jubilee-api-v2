<?php

namespace App\Models\Services;

use App\Models\Site;
use App\Models\SiteAd;

class SiteAdService extends ModelService
{
    private $site_ad;

    public function __construct(SiteAd $site_ad)
    {
        $this->site_ad = $site_ad;
        $this->model = $site_ad;
    }

    public static function create(
        Site $site,
        int $section,
        string $name,
        string $source,
        int $source_id,
        int $platform,
        int $disclosure,
        int $border,
        int $organic,
        string $min_slide,
        string $max_slide,
        string $tags
    )
    {
        $site_ad = new SiteAd();
        $site_ad->site_id = $site->id;
        $site_ad->account_id = $site->account_id;
        $site_ad->section = $section;
        $site_ad->name = $name;
        $site_ad->source = $source;
        $site_ad->source_id = $source_id;
        $site_ad->platform = $platform;
        $site_ad->disclosure = $disclosure;
        $site_ad->border = $border;
        $site_ad->organic = $organic;
        $site_ad->min_slide = $min_slide;
        $site_ad->max_slide = $max_slide;
        $site_ad->tags = $tags;

        $site_ad->save();

        return $site_ad;
    }

    public static function getAd(int $id)
    {
        $ad = SiteAd::where('id', '=', $id)->first();

        return $ad;
    }

    public function update(
        string $name,
        string $source,
        int $source_id,
        int $platform,
        int $disclosure,
        int $border,
        int $organic,
        string $min_slide,
        string $max_slide,
        string $tags
    )
    {
        $this->site_ad->name = $name;
        $this->site_ad->source = $source;
        $this->site_ad->source_id = $source_id;
        $this->site_ad->platform = $platform;
        $this->site_ad->disclosure = $disclosure;
        $this->site_ad->border = $border;
        $this->site_ad->organic = $organic;
        $this->site_ad->min_slide = $min_slide;
        $this->site_ad->max_slide = $max_slide;
        $this->site_ad->tags = $tags;
        $this->site_ad->save();

        return $this->site_ad->fresh();
    }
}
