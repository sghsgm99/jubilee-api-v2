<?php

namespace App\Models\Services;

use App\Models\Site;
use App\Models\SiteTag;

class SiteTagService extends ModelService
{
    /**
     * @var SiteTag
     */
    private $site_tag;

    public function __construct(SiteTag $site_tag)
    {
        $this->site_tag = $site_tag;
        $this->model = $site_tag; // required
    }

    public static function create(
        Site $site,
        string $label,
        int $tag_id = null
    )
    {
        $site_tag = new SiteTag();

        $site_tag->site_id = $site->id;
        $site_tag->tag_id = $tag_id;
        $site_tag->label = $label;
        $site_tag->account_id = $site->account_id;

        $site_tag->save();

        return $site_tag;
    }

    public function update(string $label)
    {
        $this->site_tag->label = $label;
        $this->site_tag->save();

        return $this->site_tag->fresh();
    }

    public static function getListOption(int $site_id, string $keyword = null)
    {
        $query = SiteTag::whereSiteId($site_id);

        if ($keyword) {
            $query->where('label', 'LIKE', "%{$keyword}%");
        }

        $tags = $query->get(['id', 'label']);

        $filtered_tags = [];
        foreach ($tags as $tag) {
            $filtered_tags[] = [
                'id' => $tag->id,
                'label' => $tag->label,
            ];
        }

        return $filtered_tags;
    }
}
