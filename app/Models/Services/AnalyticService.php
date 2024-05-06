<?php

namespace App\Models\Services;

use App\Models\Account;
use App\Models\Analytic;
use App\Models\Channel;
use App\Models\Enums\ChannelFacebookTypeEnum;
use App\Models\Enums\ChannelPlatformEnum;
use App\Models\FacebookAd;
use App\Models\FacebookAdset;
use App\Models\FacebookCampaign;
use App\Services\FacebookService;
use Illuminate\Database\Eloquent\Builder;

class AnalyticService extends ModelService
{
    /**
     * @var Analytic
     */
     private $analytic;

//     public function __construct(Account $account)
//     {
//         $this->account = $account;
//         $this->model = $account;
//     }

    public static function facebookAnalyticsByTab(string $tab)
    {
        $channels = Channel::whereHas('channelFacebook', function(Builder $query) {
                $query->whereParentBMNotNull();
                $query->orWhere('type', ChannelFacebookTypeEnum::STANDALONE);
            })
            ->get();

        $data = [];
        foreach ($channels as $channel) {
            $insights = FacebookService::resolve($channel, true)->getFacebookInsight($channel->channelFacebook, $tab);
            if (empty($insights)) {
                continue;
            }
            if(!isset($insights['error'])) {
                foreach ($insights as $insight) {
                    $data[] = ['channel' => $channel->channelFacebook->name] + $insight;
                }
            }
        }
        return $data;
    }

    public static function getFacebookInsightByStep(
        string $step,
        string $id,
        string $rule
    )
    {
        switch ($step) {
            case 'campaign':
                $setDB = FacebookCampaign::with('channel')->where('fb_campaign_id', $id)->first();
                if(!$setDB) {
                    return [];
                }
                $channel = $setDB->channel;
                break;
            case 'adset':
                $setDB = FacebookAdset::with('campaign.channel')->where('fb_adset_id', $id)->first();
                if(!$setDB) {
                    return [];
                }
                $channel = $setDB->campaign->channel;
                break;
            case 'ad':
                $setDB = FacebookAd::with('adset.campaign.channel')->where('fb_ad_id', $id)->first();
                if(!$setDB) {
                    return [];
                }
                $channel = $setDB->adset->campaign->channel;
                break; 
        }
        return FacebookService::resolve($channel)->facebookInsights(
            $step,
            $id,
            $rule
        );
    }

    public static function fetchChannelAnalytics()
    {
        $channels = Channel::with(['analytics'])->get();
        $arr = [];

        foreach ($channels as $channel) {
            $platform = ChannelPlatformEnum::memberByValue($channel->platform);
            $insights = [];

            switch ($platform->key) {
                case 'FACEBOOK':
                    if(isset($channel->channelFacebook->ad_account) && $channel->channelFacebook->ad_account != config('facebook.test_ad_account')) {
                        // fetch facebook insights
                        $insights = FacebookService::resolve($channel, true)->facebookInsights($channel->channelFacebook);
                    }
                    break;

                default:
                    # code...
                    break;
            }

            if(!$channel->analytics) {
                // create analytics row
                $analytics = new Analytic;
                $analytics->account_id = auth()->user()->account_id;
                $analytics->channel_id = $channel->id;
                $analytics->spend = 0;
                $analytics->clicks = 0;
                $analytics->impressions = 0;
                $analytics->reach = 0;
                $analytics->save();
                $channel->analytics = $analytics;
            }
            if(isset($insights['data'][0])) {
                // update analytics if insight data is available
                $channel->analytics->spend = $insights['data'][0]['spend'] ? round($insights['data'][0]['spend'], 2) : 0;
                $channel->analytics->clicks = $insights['data'][0]['clicks'] ?? 0;
                $channel->analytics->impressions = $insights['data'][0]['impressions'] ?? 0;
                $channel->analytics->reach = $insights['data'][0]['reach'] ?? 0;
                $channel->analytics->update();
            }
            $arr[] = $channel->analytics;
        }

        return collect($arr);
    }
}
