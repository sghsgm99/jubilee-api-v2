<?php

namespace App\Models\Services;

use App\Models\Site;
use App\Models\SiteCategory;

class SiteCategoryService extends ModelService
{
    /**
     * @var SiteCategory
     */
    private $site_category;

    public function __construct(SiteCategory $site_category)
    {
        $this->site_category = $site_category;
        $this->model = $site_category; // required
    }

    public static function create(
        Site $site,
        string $label,
        int $category_id = null
    )
    {
        $site_category = new SiteCategory();

        $site_category->site_id = $site->id;
        $site_category->category_id = $category_id;
        $site_category->label = $label;
        $site_category->account_id = $site->account_id;

        $site_category->save();

        return $site_category;
    }

    public function update(string $label)
    {
        $this->site_category->label = $label;
        $this->site_category->save();

        return $this->site_category->fresh();
    }

    public static function getListOption(int $site_id, string $keyword = null)
    {
        $query = SiteCategory::whereSiteId($site_id);

        if ($keyword) {
            $query->where('label', 'LIKE', "%{$keyword}%");
        }

        $categories = $query->get(['id', 'label']);
        $filtered_categories = [];
        foreach ($categories as $category) {
            $filtered_categories[] = [
                'id' => $category->id,
                'label' => $category->label,
            ];
        }

        return $filtered_categories;
    }
}
