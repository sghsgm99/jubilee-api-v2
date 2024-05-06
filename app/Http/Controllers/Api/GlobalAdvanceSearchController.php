<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enums\ArticleStatusEnum;
use App\Models\Enums\ArticleTypeEnum;
use App\Models\Enums\CampaignInAppStatusEnum;
use App\Models\Enums\CampaignStatusEnum;
use App\Models\Enums\ChannelPlatformEnum;
use App\Models\Enums\ChannelStatusEnum;
use App\Models\Enums\FacebookCampaignObjectiveEnum;
use App\Models\Enums\SitePlatformEnum;
use App\Models\Enums\SiteStatusEnum;
use App\Models\Services\GlobalAdvanceSearchService;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

class GlobalAdvanceSearchController extends Controller
{
    public static function apiRoutes()
    {
        Route::get('global-advance-search', [GlobalAdvanceSearchController::class, 'collection']);
    }

    public function collection(Request $request)
    {
        return GlobalAdvanceSearchService::globalSearch(
            $request->input('per_page', 10),
            $request->input('search', null),
            ArticleTypeEnum::memberByValue($request->input('article_type', null)),
            ArticleStatusEnum::memberByValue($request->input('article_status', null)),
            $request->input('article_owner', null),
            SitePlatformEnum::memberByValue($request->input('site_platform', null)),
            SiteStatusEnum::memberByValue($request->input('site_status', null)),
            $request->input('site_owner', null),
            ChannelPlatformEnum::memberByValue($request->input('channel_platform', null)),
            ChannelStatusEnum::memberByValue($request->input('channel_status', null)),
            $request->input('channel_owner', null),
            $request->input('campaign_channel', null),
            CampaignStatusEnum::memberByValue($request->input('campaign_status', null)),
            $request->input('campaign_owner', null),
            $request->input('fb_channel_id', null),
            FacebookCampaignObjectiveEnum::memberByValue(($request->input('fb_campaign_objective', null))),
            CampaignInAppStatusEnum::memberByValue($request->input('fb_status', null)),
            $request->input('fb_owner', null)
        );
    }
}
