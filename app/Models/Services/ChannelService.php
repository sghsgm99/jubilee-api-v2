<?php

namespace App\Models\Services;

use App\Models\Channel;
use App\Models\User;
use App\Models\Article;
use App\Models\Services\ChannelFacebookService;
use App\Models\ChannelFacebook;
use App\Models\Enums\ChannelFacebookTypeEnum;
use App\Models\Enums\ChannelStatusEnum;
use App\Models\Enums\ChannelPlatformEnum;
use App\Models\Enums\FacebookAdSetBidStrategyEnum;
use App\Models\Enums\FacebookAdSetBillingEventEnum;
use App\Models\Enums\FacebookBudgetTypeEnum;
use App\Models\Enums\FacebookCampaignBuyingTypeEnum;
use App\Models\Enums\FacebookCampaignObjectiveEnum;
use App\Models\Enums\FacebookCampaignStatusEnum;
use App\Models\Enums\FacebookTimezoneEnum;
use App\Models\Enums\FacebookVerticalEnum;
use App\Services\FacebookAdSetService;
use App\Services\FacebookAdService;
use App\Services\ResponseService;
use App\Traits\ImageModelServiceTrait;
use FacebookAds\Api;
use FacebookAds\Logger\CurlLogger;
use FacebookAds\Object\Ad;
use FacebookAds\Object\AdAccount;
use FacebookAds\Object\Campaign;
use FacebookAds\Object\Fields\CampaignFields;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redirect;

use FacebookAds\Object\AdRule;
use FacebookAds\Object\Fields\AdAccountFields;
use FacebookAds\Object\Fields\TargetingFields;
use FacebookAds\Object\Targeting;
use FacebookAds\Object\Values\CampaignBidStrategyValues;
use FacebookAds\Object\Values\CampaignObjectiveValues;
use FacebookAds\Object\Values\CampaignSpecialAdCategoriesValues;

use App\Services\FacebookCampaignService;
use App\Services\FacebookService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ChannelService extends ModelService
{
    use ImageModelServiceTrait;

    /**
     * @var Channel
     */
    private $channel;

    public function __construct(Channel $channel)
    {   
        $this->channel = $channel;
        $this->model = $channel; // required
    }

    public static function create(
        string $title,
        string $fb_page_id = null,
        FacebookVerticalEnum $fb_vertical = null,
        FacebookTimezoneEnum $fb_timezone = null,
        string $fb_ad_account = null,
        string $content,
        string $user_access_token = null,
        string $fb_user_id = null,
        string $fb_access_token = null,
        ChannelPlatformEnum $platform,
        ChannelStatusEnum $status,
        ChannelFacebookTypeEnum $type,
        array $images = []
    ) {

        try {
            // start db transaction
            DB::beginTransaction();

            // if Child BM API call is success
            $channel = new Channel();

            $channel->title = $title;
            $channel->content = $content;
            $channel->platform = $platform;
            $channel->status = $status;
            $channel->user_access_token = $user_access_token;
            $channel->user_id = Auth::user()->id;
            $channel->account_id = Auth::user()->account_id;
            $channel->save();

            // Channel Facebook Service : Create
            $channel_facebook = ChannelFacebookService::create(
                $channel,
                $title,
                $user_access_token,
                $fb_page_id,
                $fb_user_id,
                $fb_vertical,
                $fb_timezone,
                $fb_ad_account,
                $fb_access_token,
                $type
            );

            if (isset($channel_facebook['error'])) {
                return $channel_facebook;
            }

            foreach ($images as $image) {
                $filename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME) . '_' . time();
                $file = $channel->FileServiceFactory()->uploadFile($image, $filename);

                $channel->Service()->attachImage($image, $file['name']);
            }

            DB::commit();

            return $channel;
        } catch (\Exception $e) {
            // rollback db transactions
            DB::rollBack();

            // return to previous page with errors
            return ['message' => $e->getMessage(), 'error' => true];
        }
    }

    public function update(
        string $title,
        string $fb_page_id = null,
        string $fb_ad_account = null,
        string $fb_access_token = null,
        string $content,
        ChannelStatusEnum $status
    ) {
        try {
            // start db transaction
            DB::beginTransaction();

            // Channel Facebook Service : Update
            $channel_facebook = $this->channel->channelFacebook->Service()->update(
                $title,
                $fb_ad_account,
                $fb_page_id,
                $fb_access_token,
            );

            if (isset($channel_facebook['error'])) {
                return $channel_facebook;
            }

            $this->channel->title = $title;
            $this->channel->content = $content;
            $this->channel->status = $status;


            DB::commit();

            $this->channel->save();
            return $this->channel->fresh();
        } catch (\Exception $e) {
            // rollback db transactions
            DB::rollBack();

            // return to previous page with errors
            return ['message' => $e->getMessage(), 'error' => true];
        }
    }

    public function uploadImages(array $images = [])
    {
        foreach ($images as $image) {
            $filename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME) . '_' . time();
            $file = $this->channel->FileServiceFactory()->uploadFile($image, $filename);

            $this->channel->Service()->attachImage($image, $file['name']);
        }

        return $this->channel->fresh()->images;
    }

    public function getPermission(string $code = null)
    {
        // redirects
        switch ($this->channel->platform->value) {
            case ChannelPlatformEnum::FACEBOOK:
                return $this->facebookPermission($code);
                break;
        }
    }

    public function deleteChannel()
    {
        try {
            DB::beginTransaction();

            if ($this->channel->facebookCampaigns->count() > 0) {
                return ['error' => true, 'message' => 'Channel cannot be deleted. Relationship with campaigns exist.'];
            }
        
            if ($this->channel->channelFacebook) {
                $channel_facebook = $this->channel->channelFacebook->Service()->deleteChannelFacebook($this->channel);
                if (isset($channel_facebook['error'])) {
                    return $channel_facebook;
                }
            }
        
            if ($this->channel->analytics) {
                $this->channel->analytics->delete();
            }

            $delete = $this->channel->delete();
            
            DB::commit();
            
            return $delete;

        } catch (\Throwable $th) {
            DB::rollBack();
            return [
                'error' => true,
                'message' => $th->getMessage()
            ];
        }

    }
    
    public static function BulkDelete(array $ids)
    {

        $archived = [];
        $unarchived = [];

        foreach ($ids as $id) {

            $channel = Channel::find($id);
            if ($channel->status->isNot(ChannelStatusEnum::PUBLISHED())) {
                $deleteChannel = $channel->Service()->deleteChannel();

                if (is_array($deleteChannel)) {
                    if ($deleteChannel['error'] == true) {
                        $unarchived[] = [
                            "id" => $id,
                            "title" => $channel->title,
                            "status" => "Unarchived",
                            "message" => $deleteChannel['message']
                        ];
                    }
                } else {
                    $archived[] = [
                        "id" => $id,
                        "title" => $channel->title,
                        "status" => "Archived",
                        "message" => "Channel was archived."
                    ];
                }
            } else {
                $unarchived[] = [
                    "id" => $id,
                    "title" => $channel->title,
                    "status" => "Published",
                    "message" => "Channel cannot be deleted because the current status is PUBLISHED"
                ];
            }
        }

        $response = [
            "archived" => $archived,
            "unarchived" => $unarchived
        ];
        return $response;
    }

    private function facebookPermission($code)
    {

        $app_url = env('APP_URL');
        $redirect_uri = urlencode("{$app_url}/api/v1/facebook/accept-code/");
        $response_uri = urlencode("{$app_url}/");

        // user app_id
        $app_id = $this->channel->api_key;
        // user app_secret
        $app_secret = $this->channel->api_secret_key;

        if ($code) {
            // Get Short lived access token
            $short_access_token = Http::get("https://graph.facebook.com/v12.0/oauth/access_token?redirect_uri={$redirect_uri}&client_id={$app_id}&client_secret={$app_secret}&code={$code}");

            if ($short_access_token->successful()) {
                $short = json_decode($short_access_token, true);

                // Get Long lived access token
                $long_access_token = Http::get("https://graph.facebook.com/oauth/access_token?grant_type=fb_exchange_token&client_id={$app_id}&client_secret={$app_secret}&fb_exchange_token={$short['access_token']}");

                if ($long_access_token->successful()) {
                    $long = json_decode($long_access_token, true);
                    $long['status'] = $long_access_token->status();
                    $this->channel->access_token = $long['access_token'];
                    $this->channel->api_callback = $long;
                    $this->channel->save();
                    return $this->channel->fresh();
                }
            }

            return ['status' => 'failed', 'msg' => 'Token unsuccessuflly generated!'];
        } else {
            return Redirect::to("https://www.facebook.com/v6.0/dialog/oauth?client_id={$app_id}&redirect_uri={$redirect_uri}&state=channel_{$this->channel->id}");
        }
    }

    public function getFacebookCampaigns(string $status)
    {

        $status = $status != '' ? explode(',', $status) : [
            FacebookCampaignStatusEnum::ACTIVE,
            FacebookCampaignStatusEnum::PAUSED,
        ];

        return FacebookCampaignService::resolve($this->channel)->getCampaigns(
            $status
        );
    }

    public function getSingleFacebookCampaign(int $campaign_id)
    {

        return FacebookCampaignService::resolve($this->channel)->getSingleCampaign($campaign_id);
    }

    public function createFacebookCampaign(
        string $name,
        string $objective,
        FacebookCampaignStatusEnum $status,
        array $special_ad_category
    ) {


        return FacebookCampaignService::resolve($this->channel)->createCampaign(
            $name,
            $objective,
            $status,
            $special_ad_category
        );
    }

    public function updateFacebookCampaign(
        int $campaign_id,
        string $name,
        FacebookCampaignObjectiveEnum $objective,
        FacebookCampaignStatusEnum $status,
        array $special_ad_category
    ) {

        return FacebookCampaignService::resolve($this->channel)->updateCampaign(
            $campaign_id,
            $name,
            $objective,
            $status,
            $special_ad_category
        );
    }

    public function deleteFacebookCampaign($campaign_id)
    {
        return FacebookCampaignService::resolve($this->channel)->deleteCampaign($campaign_id);
    }

    public function getFacebookAdSets(string $status)
    {
        $status = $status != '' ? explode(',', $status) : [
            FacebookCampaignStatusEnum::ACTIVE,
            FacebookCampaignStatusEnum::PAUSED,
        ];

        return FacebookAdSetService::resolve($this->channel)->getAdSets(
            $status
        );
    }

    public function getSingleFacebookAdSet(int $adset_id)
    {
        return FacebookAdSetService::resolve($this->channel)->getSingleAdSet($adset_id);
    }

    public function createFacebookAdSet(
        int $campaign_id,
        string $name,
        FacebookAdSetBillingEventEnum $billing_event,
        int $bid_amount,
        FacebookAdSetBidStrategyEnum $bid_strategy,
        FacebookBudgetTypeEnum $budget_type,
        int $budget_amount,
        string $start_time,
        string $end_time,
        array $targeting,
        FacebookCampaignStatusEnum $status
    ) {
        return FacebookAdSetService::resolve($this->channel)->createAdSet(
            $campaign_id,
            $name,
            $billing_event,
            $bid_amount,
            $bid_strategy,
            $budget_type,
            $budget_amount,
            $start_time,
            $end_time,
            $targeting,
            $status
        );
    }

    public function updateFacebookAdSet(
        int $adset_id,
        string $name,
        FacebookAdSetBillingEventEnum $billing_event,
        FacebookAdSetBidStrategyEnum $bid_strategy,
        int $bid_amount,
        FacebookBudgetTypeEnum $budget_type,
        int $budget_amount,
        string $start_time,
        string $end_time,
        array $targeting,
        FacebookCampaignStatusEnum $status
    ) {
        return FacebookAdSetService::resolve($this->channel)->updateAdSet(
            $adset_id,
            $name,
            $billing_event,
            $bid_strategy,
            $bid_amount,
            $budget_type,
            $budget_amount,
            $start_time,
            $end_time,
            $targeting,
            $status,
        );
    }

    public function deleteFacebookAdSet(int $adset_id)
    {
        return FacebookAdSetService::resolve($this->channel)->deleteAdSet($adset_id);
    }


    public function getFacebookAds(
        string $status,
        string $campaign_id,
        string $adset_id
    ) {
        $status = $status != '' ? explode(',', $status) : [
            FacebookCampaignStatusEnum::ACTIVE,
            FacebookCampaignStatusEnum::PAUSED,
        ];

        return FacebookAdService::resolve($this->channel)->getAds(
            $status,
            $campaign_id,
            $adset_id
        );
    }

    public function createFacebookAd(
        string $adset_id,
        int $article_id,
        string $status
    ) {
        $article = Article::find($article_id);
        return FacebookAdService::resolve($this->channel)->createAd(
            $adset_id,
            $article,
            $status
        );
    }

    public function deleteFacebookAd(
        string $ad_id
    ) {
        return FacebookAdService::resolve($this->channel)->deleteAd($ad_id);
    }

    public function getSingleFacebookAd(
        string $ad_id
    ) {
        return FacebookAdService::resolve($this->channel)->getSingleAd(
            $ad_id
        );
    }

    public function getFacebookInsight()
    {
        return FacebookService::resolve($this->channel)->facebookInsights();
    }

    public function getFacebookDetailedInsight()
    {
        return FacebookService::resolve($this->channel)->facebookDetailedInsights();
    }

    public function targetingSearchLocationFacebookAdSet(string $q)
    {
        return FacebookAdSetService::resolve($this->channel)->targetingSearchLocation($q);
    }

    public function targetingSearchCountry(string $q)
    {
        return FacebookAdSetService::resolve($this->channel)->targetingSearchCountry($q);
    }

    public function targetingSearchLocaleFacebookAdSet(string $q)
    {
        return FacebookAdSetService::resolve($this->channel)->targetingSearchLocale($q);
    }

    public function targetingSearchInterestFacebookAdSet(string $q)
    {
        return FacebookAdSetService::resolve($this->channel)->targetingSearchInterest($q);
    }

    public function targetingSearchBehaviorFacebookAdSet($q)
    {
        return FacebookAdSetService::resolve($this->channel)->targetingSearchBehavior($q);
    }

    public function targetingSearchEducationSchoolFacebookAdSet(string $q)
    {
        return FacebookAdSetService::resolve($this->channel)->targetingSearchEducationSchool($q);
    }

    public function targetingSearchEducationMajorFacebookAdSet(string $q)
    {
        return FacebookAdSetService::resolve($this->channel)->targetingSearchEducationMajor($q);
    }

    public function targetingSearchWorkEmployerFacebookAdSet(string $q)
    {
        return FacebookAdSetService::resolve($this->channel)->targetingSearchWorkEmployer($q);
    }

    public function targetingSearchJobTitleFacebookAdSet(string $q)
    {
        return FacebookAdSetService::resolve($this->channel)->targetingSearchJobTitle($q);
    }
    public function targetingSearchCategoryFacebookAdSet(string $class, string $q)
    {
        return FacebookAdSetService::resolve($this->channel)->targetingSearchCategory(
            $class,
            $q
        );
    }
}
