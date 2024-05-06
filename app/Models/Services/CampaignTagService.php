<?php

namespace App\Models\Services;

use App\Models\CampaignTag;

class CampaignTagService extends ModelService
{
    /**
     * @var CampaignTag
     */
    private $campaign_tag;

    public function __construct(CampaignTag $campaign_tag)
    {
        $this->campaign_tag = $campaign_tag;
        $this->model = $campaign_tag; // required
    }

    public static function create(string $label, string $color): CampaignTag
    {
        $campaign_tag = new CampaignTag();

        $campaign_tag->label = $label;
        $campaign_tag->color = $color;
        $campaign_tag->account_id = auth()->user()->account_id;

        $campaign_tag->save();

        return $campaign_tag;
    }

    public function update(string $label, string $color): CampaignTag
    {
        $this->campaign_tag->label = $label;
        $this->campaign_tag->color = $color;
        $this->campaign_tag->save();

        return $this->campaign_tag->fresh();
    }

    public static function getListOption(string $keyword = null): array
    {
        $query = CampaignTag::query();

        if ($keyword) {
            $query->where('label', 'LIKE', "%{$keyword}%");
        }

        return $query->get(['id', 'label', 'color'])->toArray();
    }
}
