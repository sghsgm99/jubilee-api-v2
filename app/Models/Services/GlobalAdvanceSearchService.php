<?php

namespace App\Models\Services;

use App\Models\Article;
use App\Models\Campaign;
use App\Models\Channel;
use App\Models\Enums\ArticleStatusEnum;
use App\Models\Enums\ArticleTypeEnum;
use App\Models\Enums\CampaignInAppStatusEnum;
use App\Models\Enums\CampaignStatusEnum;
use App\Models\Enums\ChannelPlatformEnum;
use App\Models\Enums\ChannelStatusEnum;
use App\Models\Enums\FacebookCampaignObjectiveEnum;
use App\Models\Enums\SitePlatformEnum;
use App\Models\Enums\SiteStatusEnum;
use App\Models\FacebookCampaign;
use App\Models\Site;

class GlobalAdvanceSearchService extends ModelService
{
    /*
    * Filters
    * Article = type, status, owner
    * Sites = platform, status, creator
    * Channels = platform, status, creator
    * Campaigns = channel, status, creator
    * FB Campaigns = channel_id, objective, status, owner
    */
    public static function globalSearch(
        int $per_page,
        string $search = null,
        ArticleTypeEnum $article_type = null,
        ArticleStatusEnum $article_status = null,
        int $article_owner = null,
        SitePlatformEnum $site_platform = null,
        SiteStatusEnum $site_status = null,
        int $site_owner = null,
        ChannelPlatformEnum $channel_platform = null,
        ChannelStatusEnum $channel_status = null,
        int $channel_owner = null,
        int $campaign_channel = null,
        CampaignStatusEnum $campaign_status = null,
        int $campaign_owner = null,
        int $fb_channel_id = null,
        FacebookCampaignObjectiveEnum $fb_campaign_objective = null,
        CampaignInAppStatusEnum $fb_status = null,
        int $fb_owner = null
    )
    {
        // Filter By Article
        $article_filter = [];
        if($article_type->isNotUndefined()){
            $query = ['type', '=', $article_type->value];
            array_push($article_filter, $query);
        }

        if($article_status->isNotUndefined()){
            $query = ['status', '=', $article_status->value];
            array_push($article_filter, $query);
        }

        if($article_owner){
            $query = ['user_id', '=', $article_owner];
            array_push($article_filter, $query);
        }

        // Filter By Site
        $site_filter = [];
        if($site_platform->isNotUndefined()){
            $query = ['platform', '=', $site_platform->value];
            array_push($site_filter, $query);
        }

        if($site_status->isNotUndefined()){
            $query = ['status', '=', $site_status->value];
            array_push($site_filter, $query);
        }

        if($site_owner){
            $query = ['user_id', '=', $site_owner];
            array_push($site_filter, $query);
        }

        // Filter By Channel
        $channel_filter = [];
        if($channel_platform->isNotUndefined()){
            $query = ['platform', '=', $channel_platform->value];
            array_push($channel_filter, $query);
        }

        if($channel_status->isNotUndefined()){
            $query = ['status', '=', $channel_status->value];
            array_push($channel_filter, $query);
        }

        if($channel_owner){
            $query = ['user_id', '=', $channel_owner];
            array_push($channel_filter, $query);
        }

        // Filter By Campaign
        $campaign_filter = [];
        if($campaign_channel){
            $query = ['channel_id', '=', $campaign_channel];
            array_push($campaign_filter, $query);
        }

        if($campaign_status->isNotUndefined()){
            $query = ['status', '=', $campaign_status->value];
            array_push($campaign_filter, $query);
        }

        if($campaign_owner){
            $query = ['user_id', '=', $campaign_owner];
            array_push($campaign_filter, $query);
        }

        // Filter By FB Campaign
        $fb_campaign_filter = [];
        if($fb_channel_id){
            $query = ['channel_id', '=', $fb_channel_id];
            array_push($fb_campaign_filter, $query);
        }

        if($fb_campaign_objective->isNotUndefined()){
            $query = ['objective', '=', $fb_campaign_objective->value];
            array_push($fb_campaign_filter, $query);
        }

        if($fb_status->isNotUndefined()){
            $query = ['status', '=', $fb_status->value];
            array_push($fb_campaign_filter, $query);
        }

        if($fb_owner){
            $query = ['user_id', '=', $fb_owner];
            array_push($fb_campaign_filter, $query);
        }

        $articles_results = Article::query()
                                ->where($article_filter)
                                ->where(function($query) use ($search){
                                    $query->where('title', 'like', "%{$search}%");
                                })
                                ->paginate($per_page);

        $sites_results = Site::query()
                            ->where($site_filter)
                            ->where(function($query) use ($search){
                                $query->where('name', 'like', "%{$search}%")
                                            ->orWhere('url', 'like', "%{$search}%");
                            })
                            ->paginate($per_page);


        $channels_results = Channel::query()
                            ->where($channel_filter)
                            ->where(function($query) use ($search){
                                $query->where('title', 'like', "%{$search}%");
                            })
                            ->paginate($per_page);

        $campaigns_results = Campaign::query()
                            ->where($campaign_filter)
                            ->where(function($query) use ($search){
                                $query->where('title', 'like', "%{$search}%");
                            })
                            ->paginate($per_page);

        $fb_campaign_results = FacebookCampaign::query()
                            ->where($fb_campaign_filter)
                            ->where(function($query) use ($search){
                                $query->where('title', 'like', "%{$search}%");
                            })
                            ->paginate($per_page);

        return [
            'Articles' => (!$articles_results->isEmpty()) ? $articles_results : null,
            'Sites' => (!$sites_results->isEmpty()) ? $sites_results : null,
            'Channels' => (!$channels_results->isEmpty()) ? $channels_results : null,
            'Campaigns' =>(!$campaigns_results->isEmpty()) ? $campaigns_results : null,
            'FB_Campaigns' => (!$fb_campaign_results->isEmpty()) ? $fb_campaign_results : null
        ];

    }
}
