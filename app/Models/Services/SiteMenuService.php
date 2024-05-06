<?php

namespace App\Models\Services;

use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\SiteMenu;
use App\Models\SitePageMenu;
use App\Models\Enums\SiteMenuTypeEnum;

class SiteMenuService extends ModelService
{
    /**
     * @var SiteMenu
     */
    private $site_menu;

    public function __construct(SiteMenu $site_menu)
    {
        $this->site_menu = $site_menu;
        $this->model = $site_menu; // required
    }

    /**
     * title
     * sort
     * status
     * site_id
     * account_id
     */
    public static function create(
        Site $site,
        string $title,
        int $sort,
        bool $is_top,
        bool $is_bottom,
        int $status,
        int $type
    )
    {
        $site_menu = new SiteMenu();
        $site_menu->title = $title;
        $site_menu->slug = str_slug($title);
        $site_menu->sort = $sort;
        $site_menu->is_top = $is_top;
        $site_menu->is_bottom = $is_bottom;
        $site_menu->status = $status;
        $site_menu->type = $type;
        $site_menu->site_id = $site->id;
        $site_menu->account_id = $site->account_id;

        $site_menu->save();

        return $site_menu;
    }

    public function update(
        string $title,
        int $sort,
        int $type
    )
    {
        $this->site_menu->title = $title;
        $this->site_menu->sort = $sort;
        $this->site_menu->type = $type;
        $this->site_menu->save();

        return $this->site_menu->fresh();
    }

    public static function getListOption(int $site_id, string $keyword = null)
    {
        $query = SiteMenu::whereSiteId($site_id);

        if ($keyword) {
            $query->where('title', 'LIKE', "%{$keyword}%");
        }

        $menus = $query->get(['id', 'title']);
        $filtered_menus = [];
        foreach ($menus as $menu) {
            $filtered_menus[] = [
                'id' => $menu->id,
                'title' => $menu->title,
            ];
        }

        return $filtered_menus;
    }

    public function syncPage(int $page_id, int $type)
    {
        if (empty($page_id)) {
            return $this->site_menu->pages()->detach();
        }

        if ($type == SiteMenuTypeEnum::CATEGORY)
            return $this->site_menu->pages()->detach($page_id);

        return $this->site_menu->pages()->sync($page_id);
    }
}
