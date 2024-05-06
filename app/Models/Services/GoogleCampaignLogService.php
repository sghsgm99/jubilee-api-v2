<?php

namespace App\Models\Services;

use App\Models\GoogleCampaignLog;

class GoogleCampaignLogService extends ModelService
{
    /**
     * @var GoogleCampaignLog
     */
    private $campaign_log;

    public function __construct(GoogleCampaignLog $campaign_log)
    {
        $this->campaign_log = $campaign_log;
        $this->model = $campaign_log; // required
    }

    public static function create(
        string $ip,
        string $link_url,
        string $user_agent,
        string $referrer = null,
        string $type = null,
        int $position = null
    )
    {
        $campaign_log = new GoogleCampaignLog();
        $campaign_log->ip = $ip;
        $campaign_log->link_url = $link_url;
        $campaign_log->user_agent = $user_agent;
        $campaign_log->referrer = $referrer;
        $campaign_log->type = $type;
        $campaign_log->position = $position;
        $campaign_log->save();

        return $campaign_log->fresh();
    }
}
