<?php

namespace App\Models\Services;

use App\Http\Resources\CampaignTagResource;
use App\Http\Resources\ImageResource;
use App\Models\Channel;
use App\Models\Campaign;
use App\Models\Enums\CampaignTypeEnum;
use App\Models\Enums\FacebookCallToActionEnum;
use App\Models\User;
use App\Models\Article;
use App\Models\Site;
use App\Models\Enums\CampaignStatusEnum;
use App\Models\Enums\ChannelPlatformEnum;
use App\Models\Enums\FacebookCampaignStatusEnum;
use App\Models\Enums\FacebookCustomEventTypeEnum;
use App\Services\FacebookAdService;
use App\Services\FacebookAdSetService;
use App\Services\FacebookCampaignService;
use App\Services\FacebookTestAdService;
use App\Services\FacebookTestAdSetService;
use App\Services\FacebookTestCampaignService;
use App\Traits\ImageModelServiceTrait;
use Carbon\Carbon;
use Google\Service\Dfareporting\Resource\Campaigns;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CampaignService extends ModelService
{
    use ImageModelServiceTrait;

    /**
     * @var Channel
     */
    private $campaign;

    public function __construct(Campaign $campaign)
    {
        $this->campaign = $campaign;
        $this->model = $campaign; // required
    }

    public static function createCampaign(
        CampaignTypeEnum $campaignTypeEnum,
        string $title,
        string $description,
        Channel $channel,
        Site $site,
        array $data_preferences,
        CampaignStatusEnum $campaignStatusEnum,
        string $campaign_id = null,
        string $adset_id = null,
        string $ad_id = null,
        Article $article = null,
        string $ad_account,
        string $primary_text = null,
        string $headline = null,
        string $ad_description = null,
        string $display_link = null,
        FacebookCallToActionEnum $call_to_action = null
    ): Campaign
    {
        $campaign = new Campaign();

        $campaign->title = $title;
        $campaign->description = $description;
        $campaign->channel_api_preferences = [
            'campaign_id' => $campaign_id,
            'adset_id' => $adset_id,
            'ad_id' => $ad_id,
            'facebook_status' => FacebookCampaignStatusEnum::PAUSED
        ];
        $campaign->data_preferences = $data_preferences;
        $campaign->channel_id = $channel->id;
        $campaign->article_id = $article->id ?? null;
        $campaign->site_id = $site->id;
        $campaign->status = $campaignStatusEnum;
        $campaign->type = $campaignTypeEnum;
        $campaign->primary_text = $primary_text;
        $campaign->headline = $headline;
        $campaign->ad_account = $ad_account;
        $campaign->ad_description = $ad_description;
        $campaign->display_link = $display_link;
        $campaign->call_to_action = $call_to_action;
        $campaign->user_id = auth()->user()->id;
        $campaign->account_id = auth()->user()->account_id;
        $campaign->save();

        return $campaign;
    }

    private static function rollbackCreate(
        Channel $channel,
        string $campaign_id = null,
        string $adset_id = null,
        string $ad_id = null
    ): ?string
    {
        $page = null;
        if ($campaign_id) {
            FacebookCampaignService::resolve($channel)->deleteCampaign($campaign_id);
        } else {
            $page = $page ?? 'campaign';
        }

        if ($adset_id) {
            FacebookAdSetService::resolve($channel)->deleteAdset($adset_id);
        } else {
            $page = $page ?? 'adset';
        }

        if ($ad_id) {
            FacebookAdService::resolve($channel)->deleteAd($ad_id);
        } else {
            $page = $page ?? 'ad';
        }

        return $page;
    }

    public static function create(
        string $title,
        string $description,
        array $channel_api_preferences,
        string $channel_id,
        string $article_id,
        string $site_id,
        array $data,
        CampaignStatusEnum $status,
        string $ad_account
    ) 
    {

        // check module ids
        try {
            $ch = Channel::findOrFail($channel_id);
            $article = Article::findOrFail($article_id);
            $site = Site::findOrFail($site_id);
        } catch (ModelNotFoundException $e) {
            return [
                'status' => 'error',
                'model' => $e->getModel(),
                'message' => $e->getMessage()
            ];
        }

        if (!$ch->channelFacebook) {
            return [
                'status' => 'error',
                'message' => 'Channel is not a Facebook Platform'
            ];
        }

        $campaign_id = null;
        $adset_id = null;
        $ad_id = null;

        DB::beginTransaction();

        try {
            if ($status->isNot(CampaignStatusEnum::DRAFT())) {
                // create fb campagin
                $campaign = FacebookCampaignService::resolve($ch)->createCampaign(
                    $title,
                    $data['campaign']['objective'],
                    $special_ad_category ?? [],
                    $ad_account
                );
                $campaign_id = $campaign['id'];

                // create fb ad set
                $adset = FacebookAdSetService::resolve($ch)->createAdSet(
                    $campaign_id,
                    $title . ' - Ad Set',
                    $data['adset']['billing_event'],
                    $data['adset']['bid_amount'] ?? 0,
                    $data['adset']['bid_strategy'],
                    $data['adset']['budget_type'],
                    $data['adset']['budget_amount'],
                    $data['adset']['start_time'],
                    $data['adset']['end_time'] ?? '',
                    $data['adset']['targeting'],
                    $ad_account
                );
                $adset_id = $adset['id'];

                // create fb ad
                $ad = FacebookAdService::resolve($ch)->createAd(
                    $adset_id,
                    $article,
                    $site,
                    $ch->channelFacebook->page_id,
                    $ad_account
                );
                $ad_id = $ad['id'];
            }

            // create campaign
            $campaign = self::createCampaign(
                CampaignTypeEnum::REGULAR(),
                $title,
                $description,
                $ch,
                $site,
                $data,
                $status,
                $campaign_id,
                $adset_id,
                $ad_id,
                $article,
                $ad_account
            );

            DB::commit();

            return $campaign;
        } catch (\FacebookAds\Http\Exception\RequestException $th) {
            DB::rollBack();
            return [
                'status' => 'error',
                'page' => self::rollbackCreate($ch, $campaign_id, $adset_id, $ad_id),
                'message' => [
                    'code' => $th->getCode() ?? '',
                    'title' => $th->getErrorUserTitle() ?? '',
                    'body' => $th->getErrorUserMessage() ?? $th->getMessage(),
                ],
            ];
        }
    }

    public static function createStandalone(
        string $title,
        string $description,
        array $channel_api_preferences,
        Channel $channel,
        Site $site,
        array $data,
        CampaignStatusEnum $campaignStatusEnum,
        string $primary_text,
        string $headline,
        FacebookCallToActionEnum $call_to_action,
        UploadedFile $ad_image = null,
        string $ad_description = null,
        string $display_link = null,
        string $ad_account = null
    ) 
    {
        if (! $channel->channelFacebook) {
            return [
                'status' => 'error',
                'message' => 'Channel is not a Facebook Platform'
            ];
        }

        // convert budget amount to hundreds
        $data['adset']['budget_amount'] = $data['adset']['budget_amount'];

        $fb_campaign_id = null;
        $adset_id = null;
        $ad_id = null;

        DB::beginTransaction();
        try {
            /**
             * we need to create the campaign first to handle the file upload
             * attached the image to the campaign
             */
            $campaign = self::createCampaign(
                CampaignTypeEnum::STANDALONE(),
                $title,
                $description,
                $channel,
                $site,
                $data,
                $campaignStatusEnum,
                null,
                null,
                null,
                null,
                $ad_account,
                $primary_text,
                $headline,
                $ad_description,
                $display_link,
                $call_to_action
            );

            // handle file upload
            if ($ad_image) {
                $filename = pathinfo($ad_image->getClientOriginalName(), PATHINFO_FILENAME) . '_' . time();
                $file = $campaign->FileServiceFactory()->uploadFile($ad_image, $filename);

                // create the image model
                $image = $campaign->Service()->attachImage($ad_image, $file['name']);
                $campaign->Service()->markAsFeatured($image->id);
            }

            if ($campaignStatusEnum->isNot(CampaignStatusEnum::DRAFT())) {
                // create facebook campaign
                $fb_campaign = FacebookCampaignService::resolve($channel)->createCampaign(
                    $title,
                    $data['campaign']['objective'],
                    $special_ad_category ?? [],
                    $ad_account 
                );
                $fb_campaign_id = $fb_campaign['id'];

                // create facebook ad set
                $adset = FacebookAdSetService::resolve($channel)->createAdSet(
                    $fb_campaign_id,
                    $title . ' - Ad Set',
                    $data['adset']['billing_event'],
                    $data['adset']['bid_amount'] ?? 0,
                    $data['adset']['bid_strategy'],
                    $data['adset']['budget_type'],
                    $data['adset']['budget_amount'],
                    $data['adset']['start_time'],
                    $data['adset']['end_time'] ?? '',
                    $data['adset']['targeting'],
                    $ad_account,
                    $data['campaign']['objective'],
                    $data['adset']['pixel_id'] ?? null,
                    $data['adset']['custom_event_type'] ?? null
                );
                $adset_id = $adset['id'];

                // create facebook ad
                $ad = FacebookAdService::resolve($channel)->createStandAloneAd(
                    $adset_id,
                    $channel->channelFacebook->page_id,
                    $site,
                    $campaign,
                    $ad_account
                );
                $ad_id = $ad['id'];

                // then we need to update the campaign for facebook values
                $campaign->channel_api_preferences = [
                    'campaign_id' => $fb_campaign_id,
                    'adset_id' => $adset_id,
                    'ad_id' => $ad_id,
                    'facebook_status' => FacebookCampaignStatusEnum::PAUSED
                ];
                $campaign->save();
            }

            DB::commit();

            return $campaign;

        } catch (\FacebookAds\Http\Exception\RequestException $th) {
            DB::rollBack();
            return [
                'status' => 'error',
                'page' => self::rollbackCreate($channel, $fb_campaign_id, $adset_id, $ad_id),
                'message' => [
                    'code' => $th->getCode() ?? '',
                    'title' => $th->getErrorUserTitle() ?? '',
                    'body' => $th->getErrorUserMessage() ?? $th->getMessage(),
                ],
            ];
        }
    }

    public function update(
        string $title,
        string $description,
        string $channel_id,
        string $article_id,
        string $site_id,
        array $data,
        CampaignStatusEnum $status,
        string $ad_account = null
    ) 
    {
        if ($this->campaign->status->value == CampaignStatusEnum::DRAFT) {

            // check module ids
            try {
                $ch = Channel::findOrFail($channel_id);
                $article = Article::findOrFail($article_id);
                $site = Site::findOrFail($site_id);
            } catch (ModelNotFoundException $e) {
                return [
                    'status' => 'error',
                    'model' => $e->getModel(),
                    'message' => $e->getMessage()
                ];
            }

            if (!$ch->channelFacebook) {
                return [
                    'status' => 'error',
                    'message' => 'Channel is not a Facebook Platform'
                ];
            }

            $campaign_id = null;
            $adset_id = null;
            $ad_id = null;

            DB::beginTransaction();

            try {

                if ($status->value != CampaignStatusEnum::DRAFT) {
                    // submit to facebook marketing API
                    $campaign = FacebookCampaignService::resolve($ch)->createCampaign(
                        $title,
                        $data['campaign']['objective'],
                        $special_ad_category ?? [],
                        $ad_account
                    );
                    $campaign_id = $campaign['id'];

                    // create fb ad set
                    $adset = FacebookAdSetService::resolve($ch)->createAdSet(
                        $campaign_id,
                        $title . ' - Ad Set',
                        $data['adset']['billing_event'],
                        $data['adset']['bid_amount'] ?? 0,
                        $data['adset']['bid_strategy'],
                        $data['adset']['budget_type'],
                        $data['adset']['budget_amount'],
                        $data['adset']['start_time'],
                        $data['adset']['end_time'] ?? '',
                        $data['adset']['targeting'],
                        $ad_account
                    );
                    $adset_id = $adset['id'];

                    // create fb ad
                    $ad = FacebookAdService::resolve($ch)->createAd(
                        $adset_id,
                        $article,
                        $site,
                        $ch->channelFacebook->page_id,
                        $ad_account
                    );
                    $ad_id = $ad['id'];
                }

                $this->campaign->title = $title;
                $this->campaign->description = $description;
                $this->campaign->channel_api_preferences = [
                    'campaign_id' => $campaign_id,
                    'adset_id' => $adset_id,
                    'ad_id' => $ad_id,
                    'facebook_status' => FacebookCampaignStatusEnum::PAUSED
                ];
                $this->campaign->data_preferences = $data;
                $this->campaign->channel_id = $channel_id;
                $this->campaign->article_id = $article_id;
                $this->campaign->site_id = $site_id;
                $this->campaign->ad_account = $ad_account;
                $this->campaign->status = $status;

                $this->campaign->save();

                DB::commit();

                return $this->campaign->fresh();
            } catch (\FacebookAds\Http\Exception\RequestException $th) {

                DB::rollBack();

                $page = null;
                if ($campaign_id) {
                    // delete campaign
                    FacebookCampaignService::resolve($ch)->deleteCampaign(
                        $campaign_id
                    );
                } else {
                    $page = $page ?? 'campagin';
                }

                if ($adset_id) {
                    // delete adset
                    FacebookAdSetService::resolve($ch)->deleteAdset(
                        $adset_id
                    );
                } else {
                    $page = $page ?? 'adset';
                }

                if ($ad_id) {
                    // delete ad
                    FacebookAdService::resolve($ch)->deleteAd(
                        $ad_id
                    );
                } else {
                    $page = $page ?? 'ad';
                }

                $msg = [];
                $msg['code'] = $th->getCode() ?? '';
                $msg['title'] = $th->getErrorUserTitle() ?? '';
                $msg['body'] = $th->getErrorUserMessage() ?? $th->getMessage();

                return [
                    'status' => 'error',
                    'page' => $page,
                    'message' => $msg
                ];
            }
        }

        return [
            'error' => true,
            'message' => 'Campaign cannot be updated.'
        ];
    }

    public function updateStandalone(
        string $title,
        string $description,
        Channel $channel,
        Site $site,
        array $data,
        CampaignStatusEnum $campaignStatusEnum,
        string $primary_text,
        string $headline,
        FacebookCallToActionEnum $call_to_action,
        UploadedFile $ad_image = null,
        string $ad_description = null,
        string $display_link = null,
        string $ad_account = null
    )
    {
        if ($this->campaign->status->is(CampaignStatusEnum::DRAFT())) {
            if (! $this->campaign->type->is(CampaignTypeEnum::STANDALONE())) {
                return [
                    'status' => 'error',
                    'message' => 'Cannot update regular campaign with this process'
                ];
            }

            if (!$channel->channelFacebook) {
                return [
                    'status' => 'error',
                    'message' => 'Channel is not a Facebook Platform'
                ];
            }

            $ad_account = $ad_account ?? $channel->channelFacebook->ad_account;

            $fb_campaign_id = null;
            $adset_id = null;
            $ad_id = null;

            DB::beginTransaction();

            try {
                $this->campaign->title = $title;
                $this->campaign->description = $description;
                $this->campaign->data_preferences = $data;
                $this->campaign->channel_id = $channel->id;
                $this->campaign->site_id = $site->id;
                $this->campaign->status = $campaignStatusEnum;
                $this->campaign->primary_text = $primary_text;
                $this->campaign->headline = $headline;
                $this->campaign->call_to_action = $call_to_action;
                $this->campaign->ad_description = $ad_description;
                $this->campaign->display_link = $display_link;
                $this->campaign->ad_account = $ad_account;
                $this->campaign->save();

                // handle file upload
                if ($ad_image) {
                    $filename = pathinfo($ad_image->getClientOriginalName(), PATHINFO_FILENAME) . '_' . time();
                    $file = $this->campaign->FileServiceFactory()->uploadFile($ad_image, $filename);

                    // create the image model
                    $image = $this->campaign->Service()->attachImage($ad_image, $file['name']);
                    $this->campaign->Service()->markAsFeatured($image->id);
                }

                if($campaignStatusEnum->isNot(CampaignStatusEnum::DRAFT())) {
                    // create facebook campaign
                    $fb_campaign = FacebookCampaignService::resolve($channel)->createCampaign(
                        $title,
                        $data['campaign']['objective'],
                        $special_ad_category ?? [],
                        $ad_account
                    );
                    $fb_campaign_id = $fb_campaign['id'];
    
                    // create facebook ad set
                    $adset = FacebookAdSetService::resolve($channel)->createAdSet(
                        $fb_campaign_id,
                        $title . ' - Ad Set',
                        $data['adset']['billing_event'],
                        $data['adset']['bid_amount'] ?? 0,
                        $data['adset']['bid_strategy'],
                        $data['adset']['budget_type'],
                        $data['adset']['budget_amount'],
                        $data['adset']['start_time'],
                        $data['adset']['end_time'] ?? '',
                        $data['adset']['targeting'],
                        $ad_account
                    );
                    $adset_id = $adset['id'];
    
                    // create facebook ad
                    $ad = FacebookAdService::resolve($channel)->createStandAloneAd(
                        $adset_id,
                        $channel->channelFacebook->page_id,
                        $site,
                        $this->campaign,
                        $ad_account
                    );
                    $ad_id = $ad['id'];
                }

                // then we need to update the campaign for facebook values
                $this->campaign->channel_api_preferences = [
                    'campaign_id' => $fb_campaign_id,
                    'adset_id' => $adset_id,
                    'ad_id' => $ad_id,
                    'facebook_status' => FacebookCampaignStatusEnum::PAUSED
                ];
                $this->campaign->save();

                DB::commit();
                return $this->campaign->fresh();
            } catch (\FacebookAds\Http\Exception\RequestException $th) {
                DB::rollBack();
                return [
                    'status' => 'error',
                    // 'page' => self::rollbackCreate($channel, $fb_campaign_id, $adset_id, $ad_id),
                    'message' => [
                        'code' => $th->getCode() ?? '',
                        'title' => $th->getErrorUserTitle() ?? '',
                        'body' => $th->getErrorUserMessage() ?? $th->getMessage(),
                    ],
                ];
            }
        }

        return [
            'status' => 'error',
            'message' => 'Campaign cannot be updated.'
        ];
    }

    public function getCampagin(Campaign $campaign)
    {
        try {
            $platform = $campaign->channel->platform->value ?? $campaign->platform->value;
            switch ($platform) {
                case ChannelPlatformEnum::FACEBOOK:
                    return $this->getFacebookPlatform($campaign);
                    break;
            }
        } catch (\Throwable $th) {
            return ['error' => true, 'message' => $th->getMessage()];
        }
    }

    public function getFacebookPlatform($campaign)
    {
        if (!$campaign->data_preferences) {
            $data = [];
            // get campaign
            $fb_campaign = FacebookCampaignService::resolve($campaign->channel)->getSingleCampaign(
                $campaign->channel_api_preferences['campaign_id']
            );
            $data['campaign'] = $fb_campaign;

            // get adset
            $fb_adset = FacebookAdSetService::resolve($campaign->channel)->getSingleAdSet(
                $campaign->channel_api_preferences['adset_id']
            );
            $data['adset'] = $fb_adset;
            $data['adset']['budget_type'] = $fb_adset['daily_budget'] > 0 ? 'daily_budget' : 'lifetime_budget';
            $data['adset']['budget_amount'] = $fb_adset['daily_budget'] > 0 ? $fb_adset['daily_budget'] : $fb_adset['lifetime_budget'];

            // get ad
            $fb_ad = FacebookAdService::resolve($campaign->channel)->getSingleAd(
                $campaign->channel_api_preferences['ad_id']
            );
            $data['ad'] = $fb_ad;

            $campaign->data_preferences = $data;
        }

        $campaign->tags = $campaign->tags()->get(['id', 'label', 'color'])->toArray();

        $campaign->ad_image = ($campaign->featureImage) ? new ImageResource($campaign->featureImage) : null;

        return $campaign;
    }

    public function updateFacebookStatus(FacebookCampaignStatusEnum $facebook_status, $is_toggle = false)
    {
        if(
            !isset($this->campaign->channel_api_preferences['campaign_id']) ||
            !isset($this->campaign->channel_api_preferences['adset_id']) ||
            !isset($this->campaign->channel_api_preferences['ad_id']) ||
            !isset($this->campaign->channel_api_preferences['facebook_status'])
        ) {
            return ['status' => 'error', 'message' => 'Campaign is not active']; 
        }

        // check time validity 72 hours
        $time = Carbon::now()->diffInHours($this->campaign->created_at);
        
        if ($time > 72 && !$is_toggle) {
            return ['status' => 'error', 'message' => 'Campaign facebook status was not successfully update due to its limit of 72 hours of updating period'];
        }

        // update campaign status
        FacebookCampaignService::resolve($this->campaign->channel)->updateCampaignStatus(
            $this->campaign->channel_api_preferences['campaign_id'],
            $facebook_status
        );

        // update adset status
        FacebookAdSetService::resolve($this->campaign->channel)->updateAdSetStatus(
            $this->campaign->channel_api_preferences['adset_id'],
            $facebook_status
        );

        $end = (isset($this->campaign->data_preferences['adset']['end_time'])) 
        ? Carbon::parse($this->campaign->data_preferences['adset']['end_time'])
        : null;

        if(!$end || Carbon::now()->lessThan($end)) {
            // update ad status
            FacebookAdService::resolve($this->campaign->channel)->updateAdStatus(
                $this->campaign->channel_api_preferences['ad_id'],
                $facebook_status
            );

        }

        $this->campaign->channel_api_preferences = [
            'campaign_id' => $this->campaign->channel_api_preferences['campaign_id'],
            'adset_id' => $this->campaign->channel_api_preferences['adset_id'],
            'ad_id' => $this->campaign->channel_api_preferences['ad_id'],
            'facebook_status' => $facebook_status->value
        ];

        $this->campaign->save();

        return $this->campaign->fresh();
    }

    public function toggleFacebookStatus()
    {
        $status = $this->campaign->channel_api_preferences['facebook_status'] == "PAUSED" ? "ACTIVE" : "PAUSED";

        return $this->updateFacebookStatus(
            FacebookCampaignStatusEnum::memberByValue($status),
            true
        );
    }

    public function deleteCampaign()
    {
        $status = [];
        $platform = $this->campaign->channel->platform->value ?? $this->campaign->platform->value;
        switch ($platform) {
            case ChannelPlatformEnum::FACEBOOK:
                if ($this->campaign->status->value == CampaignStatusEnum::DRAFT) {
                    $status = ['status' => 'success'];
                } else {
                    $status = $this->deleteFacebookPreferences(
                        $this->campaign->channel,
                        $this->campaign->channel_api_preferences
                    );
                }
                break;
        }

        if (isset($status['status']) && $status['status'] == 'success' || !$this->campaign->channel) {
            return $this->campaign->delete();
        } else {
            return $status ?? ['status' => 'error', 'message' => 'page not found'];
        }
    }

    public static function BulkDelete(array $ids)
    {
        $failedDelete = [];
        $successDelete = [];

        foreach ($ids as $id) {
            $campaign = Campaign::find($id);
            $deleteCampaign = $campaign->Service()->deleteCampaign();

            if ($deleteCampaign == true) {
                $successDelete[] = [
                    "id" => $id,
                    "title" => $campaign->title,
                    "status" => "Archived",
                    "message" => "Campaign was archived."
                ];
            } else if ($deleteCampaign['status'] == 'error') {
                $failedDelete[] = [
                    "id" => $id,
                    "title" => $campaign->title,
                    "status" => "Unarchived",
                    "message" => $deleteCampaign['message']
                ];
            }
        }

        $response = [
            "archived" => $successDelete,
            "unarchived" => $failedDelete
        ];
        return $response;
    }

    public function deleteFacebookPreferences($ch, $api)
    {
        try {
            // delete campaign
            FacebookCampaignService::resolve($ch)->deleteCampaign(
                $api['campaign_id']
            );

            // delete adset
            FacebookAdSetService::resolve($ch)->deleteAdset(
                $api['adset_id']
            );

            // delete ad
            FacebookAdService::resolve($ch)->deleteAd(
                $api['ad_id']
            );

            return ['status' => 'success'];
        } catch (\FacebookAds\Http\Exception\RequestException $th) {

            $msg = [];
            $msg['title'] = $th->getErrorUserTitle() ?? '';
            $msg['body'] = $th->getErrorUserMessage() ?? $th->getMessage();

            return [
                'status' => 'error',
                'page' => 'Delete Campaign',
                'message' => $msg
            ];
        }
    }

    public function getSingleFacebookCampaignInsight()
    {
        if (!isset($this->campaign->channel_api_preferences['campaign_id'])) {
            return [
                'error' => true,
                'message' => 'Facebook Campaign ID does not exist'
            ];
        }

        $ch = $this->campaign->channel;
        return FacebookCampaignService::resolve($ch)->campaignInsights(
            $this->campaign->channel_api_preferences ?? []
        );
    }

    public static function generatePreview(
        Channel $channel,
        string $primary_text,
        string $headline,
        FacebookCallToActionEnum $call_to_action,
        string $ad_image,
        string $ad_description,
        string $display_link,
        string $ad_account
    )
    {
        return FacebookAdService::resolve($channel)->generatePreview(
            $channel->channelFacebook,
            $primary_text,
            $headline,
            $call_to_action,
            $ad_image,
            $ad_description,
            $display_link,
            $ad_account
        );
    }

    public function duplicateCampaign()
    {
        if(!$this->campaign->channel_api_preferences['campaign_id']) {
            return ['error' => true, 'message' => 'No campaign ID to duplicate'];
        }

        $campaign = FacebookCampaignService::resolve($this->campaign->channel)->duplicateCampaign($this->campaign->channel_api_preferences['campaign_id']);

        if(isset($campaign['error'])) {
            return $campaign;
        }

        $adset = FacebookAdSetService::resolve($this->campaign->channel)->duplicateAdset(
            $campaign['copied_campaign_id'],
            $this->campaign->channel_api_preferences['adset_id']
        );

        if(isset($adset['error'])) {
            return $adset;
        }

        $ad = FacebookAdService::resolve($this->campaign->channel)->duplicateAd(
            $adset['copied_adset_id'],
            $this->campaign->channel_api_preferences['ad_id']
        );

        if(isset($ad['error'])) {
            return $ad;
        }

        $campaign_detail = FacebookCampaignService::resolve($this->campaign->channel)->getSingleCampaign($campaign['copied_campaign_id']);

        $create = self::createCampaign(
            CampaignTypeEnum::memberByValue($this->campaign->type),
            $campaign_detail['name'],
            $this->campaign->description,
            $this->campaign->channel,
            $this->campaign->site,
            $this->campaign->data_preferences,
            CampaignStatusEnum::memberByValue($this->campaign->status),
            $campaign['copied_campaign_id'],
            $adset['copied_adset_id'],
            $ad['copied_ad_id'],
            $this->campaign->article,
            $this->campaign->ad_account,
            $this->campaign->primary_text,
            $this->campaign->headline,
            $this->campaign->ad_description,
            $this->campaign->display_link,
            FacebookCallToActionEnum::memberByValue($this->campaign->call_to_action)
        );

        return $create;
    }


    public function createTestSubmission(Object $request)
    {
        if(!Auth::user()->tester) {
            return 'not a tester user account';
        }

        $ids = [
            'campaign_id' => null,
            'adset_id' => null,
            'ads_id' => null,
        ];

        $channel = Channel::find($request->channel_id);
        $site = Site::find($request->site_id);

        try {
            if($request->type != 1){
                // normal
            } else {
                // standalone
                
                // create facebook campaign
                $fb_campaign = FacebookTestCampaignService::resolve($channel)->createCampaign(
                    $request->title,
                    $request->data['campaign']['objective'],
                    $special_ad_category ?? [],
                    '1743546695815548'
                );
                $ids['campaign_id'] = $fb_campaign['id'];

                // create facebook ad set
                $adset = FacebookTestAdSetService::resolve($channel)->createAdSet(
                    $ids['campaign_id'],
                    $request->title . ' - Ad Set',
                    $request->data['adset']['billing_event'],
                    $request->data['adset']['bid_amount'] ?? 0,
                    $request->data['adset']['bid_strategy'],
                    $request->data['adset']['budget_type'],
                    $request->data['adset']['budget_amount'],
                    $request->data['adset']['start_time'],
                    $request->data['adset']['end_time'] ?? '',
                    $request->data['adset']['targeting'],
                    '1743546695815548',
                    $request->data['campaign']['objective'],
                    $request->data['adset']['pixel_id'] ?? null,
                    $request->data['adset']['custom_event_type'] ?? null
                );
                $ids['adset_id'] = $adset['id'];

                // create facebook ad
                $ad = FacebookTestAdService::resolve($channel)->createStandAloneAd(
                    $ids['adset_id'],
                    $channel->channelFacebook->page_id,
                    $site,
                    $this->campaign,
                    '1743546695815548'
                );
                $ids['ads_id'] = $ad['id'];
            }

            return $ids;

        } catch (\FacebookAds\Http\Exception\RequestException $th) {
            return [
                'status' => 'error',
                'page' => self::rollbackTestCreate($channel, $ids['campaign_id'], $ids['adset_id'], $ids['ads_id']),
                'message' => [
                    'code' => $th->getCode() ?? '',
                    'title' => $th->getErrorUserTitle() ?? '',
                    'body' => $th->getErrorUserMessage() ?? $th->getMessage(),
                ],
            ];
        }
    }

    private static function rollbackTestCreate(
        Channel $channel,
        string $campaign_id = null,
        string $adset_id = null,
        string $ad_id = null
    ): ?string
    {
        $page = null;
        if ($campaign_id) {
            FacebookTestCampaignService::resolve($channel)->deleteCampaign($campaign_id);
        } else {
            $page = $page ?? 'campaign';
        }

        if ($adset_id) {
            FacebookTestAdSetService::resolve($channel)->deleteAdset($adset_id);
        } else {
            $page = $page ?? 'adset';
        }

        if ($ad_id) {
            FacebookTestAdService::resolve($channel)->deleteAd($ad_id);
        } else {
            $page = $page ?? 'ad';
        }

        return $page;
    }
}
