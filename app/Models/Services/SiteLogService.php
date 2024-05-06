<?php

namespace App\Models\Services;

use App\Models\Site;
use App\Models\SiteLog;

class SiteLogService extends ModelService
{
    /**
     * @var SiteLog
     */
    private $site_log;

    public function __construct(SiteLog $site_log)
    {
        $this->site_log = $site_log;
        $this->model = $site_log; // required
    }

    public static function create(
        Site $site,
        string $ip,
        string $type = null,
        int $position = null
    )
    {
        $site_log = new SiteLog();
        $site_log->site_id = $site->id;
        $site_log->ip = $ip;
        $site_log->type = $type;
        $site_log->position = $position;
        $site_log->save();

        return $site_log->fresh();
    }
}
