<?php

namespace App\Models\Services\Factories;

use App\Models\Enums\SitePlatformEnum;
use App\Models\Site;
use App\Services\Wordpress\WordpressService;

class SiteServiceFactory
{
    public static function resolve(Site $site)
    {
        switch ($site->platform) {
            case SitePlatformEnum::WORDPRESS():
                return WordpressService::resolve($site);
        }

        throw new \InvalidArgumentException('This site service is not available in the system.');
    }
}
