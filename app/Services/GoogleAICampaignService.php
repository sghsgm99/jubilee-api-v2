<?php

namespace App\Services;

use Google\Ads\GoogleAds\Lib\V15\GoogleAdsClient;
use Google\Ads\GoogleAds\Util\FieldMasks;
use Google\Ads\GoogleAds\Util\V15\ResourceNames;
use Google\Ads\GoogleAds\V15\Enums\CampaignStatusEnum\CampaignStatus;
use Google\Ads\GoogleAds\V15\Resources\Campaign;
use Google\Ads\GoogleAds\V15\Services\CampaignOperation;
use Google\Ads\GoogleAds\V15\Services\GoogleAdsRow;
use Google\Ads\GoogleAds\V15\Enums\CriterionTypeEnum\CriterionType;
use Google\Ads\GoogleAds\V15\Enums\KeywordMatchTypeEnum\KeywordMatchType;
use Google\Ads\GoogleAds\V15\Errors\GoogleAdsError;
use Google\Ads\GoogleAds\Lib\V15\GoogleAdsException;
use Google\Ads\GoogleAds\V15\Common\AdTextAsset;
use Google\Ads\GoogleAds\V15\Common\ResponsiveSearchAdInfo;
use Google\Ads\GoogleAds\V15\Enums\AdGroupAdStatusEnum\AdGroupAdStatus;
use Google\Ads\GoogleAds\V15\Enums\ServedAssetFieldTypeEnum\ServedAssetFieldType;
use Google\Ads\GoogleAds\V15\Resources\Ad;
use Google\Ads\GoogleAds\V15\Resources\AdGroupAd;
use Google\Ads\GoogleAds\V15\Services\AdGroupAdOperation;
use Google\Ads\GoogleAds\V15\Services\UrlSeed;
use Google\Ads\GoogleAds\V15\Common\ManualCpc;
use Google\Ads\GoogleAds\V15\Enums\AdvertisingChannelTypeEnum\AdvertisingChannelType;
use Google\Ads\GoogleAds\V15\Enums\BudgetDeliveryMethodEnum\BudgetDeliveryMethod;
use Google\Ads\GoogleAds\V15\Resources\Campaign\NetworkSettings;
use Google\Ads\GoogleAds\V15\Resources\CampaignBudget;
use Google\Ads\GoogleAds\V15\Services\CampaignBudgetOperation;
use Google\Ads\GoogleAds\V15\Enums\AdGroupStatusEnum\AdGroupStatus;
use Google\Ads\GoogleAds\V15\Enums\AdGroupTypeEnum\AdGroupType;
use Google\Ads\GoogleAds\V15\Resources\AdGroup;
use Google\Ads\GoogleAds\V15\Services\AdGroupOperation;
use Google\Ads\GoogleAds\V15\Enums\AdGroupCriterionStatusEnum\AdGroupCriterionStatus;
use Google\Ads\GoogleAds\V15\Resources\AdGroupCriterion;
use Google\Ads\GoogleAds\V15\Services\AdGroupCriterionOperation;
use Google\Ads\GoogleAds\V15\Resources\Campaign\ShoppingSetting;
use Google\Ads\GoogleAds\V15\Services\CampaignCriterionOperation;
use Google\Ads\GoogleAds\V15\Resources\CampaignCriterion;
use Google\Ads\GoogleAds\V15\Common\LocationInfo;
use Google\Ads\GoogleAds\V15\Resources\Asset;
use Google\Ads\GoogleAds\V15\Common\AdImageAsset;
use Google\Ads\GoogleAds\V15\Common\ImageAsset;
use Google\Ads\GoogleAds\V15\Services\AssetOperation;
use Google\Ads\GoogleAds\V15\Enums\AssetTypeEnum\AssetType;
use Google\Ads\GoogleAds\V15\Common\ResponsiveDisplayAdInfo;
use Google\Ads\GoogleAds\V15\Enums\PolicyTopicEntryTypeEnum\PolicyTopicEntryType;
use Google\ApiCore\ApiException;
use DateTime;
use App\Models\GoogleAICampaign;
use App\Services\OpenAIService;
use App\Services\StableDiffusionAIService;
use App\Services\GoogleAICampaignService;
use App\Services\FileService;
use App\Models\Enums\StorageDiskEnum;
use Illuminate\Support\Facades\Log;
use Google\Ads\GoogleAds\V15\Services\SearchGoogleAdsRequest;
use Google\Ads\GoogleAds\V15\Common\PolicyTopicEntry;
use Google\Ads\GoogleAds\V15\Common\PolicyTopicEvidence;
use Google\Ads\GoogleAds\V15\Enums\AdTypeEnum\AdType;

class GoogleAICampaignService extends GoogleService
{
    private const DEFAULT_IMG_SIZE = [512, 512];
    private const MARKET_IMG_SIZE = [600, 314];
    private const SQUARE_MARKET_IMG_SIZE = [300, 300];

    public function createCampaign(GoogleAICampaign $googleAICampaign)
    {
        $customerId = $googleAICampaign->customer->customer_id;
        $title = $googleAICampaign->title;
        $budget = $googleAICampaign->budget;
        $status = CampaignStatus::ENABLED; //CampaignStatus::PAUSED

        $budgetResourceName = $this->addCampaignBudget($customerId, $budget);

        // Creates the campaign.
        $campaign = new Campaign([
            'name' => $title,
            'advertising_channel_type' => AdvertisingChannelType::DISPLAY,
            'status' => $status,
            'campaign_budget' => $budgetResourceName,
            'manual_cpc' => new ManualCpc(),
        ]);

        $campaignOperation = new CampaignOperation();
        $campaignOperation->setCreate($campaign);
        
        $campaignServiceClient = $this->googleAdsClient->getCampaignServiceClient();
        $response = $campaignServiceClient->mutateCampaigns($customerId, [$campaignOperation]);

        $createdCampaignResourceName = $response->getResults()[0]->getResourceName();

        $locationIds = [
            2840,   // USA
            2124   // Canada
        ];

        $this->setCampaignTargetingCriteria($customerId, $createdCampaignResourceName, $locationIds);

        $campaignId = explode("/", $createdCampaignResourceName)[3];
        
        return $campaignId;
    }

    public function createAdGroup(GoogleAICampaign $googleAICampaign, $campaignId)
    {
        $customerId = $googleAICampaign->customer->customer_id;
        $grpname = $googleAICampaign->title . ' adgroup';
        $bid = $googleAICampaign->bid;
        $status = CampaignStatus::ENABLED;
    
        $campaignResourceName = ResourceNames::forCampaign($customerId, $campaignId);

        $adGroup = new AdGroup([
            'name' => $grpname,
            'campaign' => $campaignResourceName,
            'status' => $status,
            'type' => AdGroupType::DISPLAY_STANDARD,
            'cpc_bid_micros' => $bid * 1000000
        ]);

        $adGroupOperation = new AdGroupOperation();
        $adGroupOperation->setCreate($adGroup);
        
        $adGroupServiceClient = $this->googleAdsClient->getAdGroupServiceClient();
        $response = $adGroupServiceClient->mutateAdGroups(
            $customerId,
            [$adGroupOperation]
        );

        $adGroupId = null;

        foreach ($response->getResults() as $addedAdGroup) {
            $adGroupId = explode("/", $addedAdGroup->getResourceName())[3];
        }

        return $adGroupId;
    }

    public function createAd(GoogleAICampaign $googleAICampaign, $adGroupId)
    {
        $customerId = $googleAICampaign->customer->customer_id;
        $title = $googleAICampaign->title;
        $status = CampaignStatus::PAUSED;
    
        $adGroupResourceName = ResourceNames::forAdGroup($customerId, $adGroupId);

        $openAIService = OpenAIService::resolve();

        $prompt = $title." - generate headline without special characters, limit 20 characters";
        $text = $openAIService->generateAIText($prompt);
        $headlines[] = self::createAdTextAsset($text);
        
        $prompt = $title." - generate description without special characters, limit 80 characters";
        $text = $openAIService->generateAIText($prompt);
        $descriptions[] = self::createAdTextAsset($text);

        $stablediffusionAIService = StableDiffusionAIService::resolve();
        $image_urls = $stablediffusionAIService->generateAIImageEx($title, 1, self::DEFAULT_IMG_SIZE[0], self::DEFAULT_IMG_SIZE[1]);
        sleep(5);
        $file_name = pathinfo($image_urls[0], PATHINFO_FILENAME);
        $fs = FileService::main(StorageDiskEnum::PUBLIC_DO(), 'stable-diffusion');
        $marketing_img = $fs->uploadResizeImage($image_urls[0], $file_name.'-m', self::MARKET_IMG_SIZE[0], self::MARKET_IMG_SIZE[1]);
        $square_marketing_img = $fs->uploadResizeImage($image_urls[0], $file_name.'-sm', self::SQUARE_MARKET_IMG_SIZE[0], self::SQUARE_MARKET_IMG_SIZE[1]);

        $marketing_images = self::uploadAsset(
            $this->googleAdsClient,
            $customerId,
            $marketing_img
        );
        
        $square_marketing_images = self::uploadAsset(
            $this->googleAdsClient,
            $customerId,
            $square_marketing_img
        );

        $prompt = $title." - generate long headline without special characters, limit 80 characters";
        $long_headline = $openAIService->generateAIText($prompt);
        
        $prompt = $title." - generate business name without special characters, limit 20 characters";
        $business_name = $openAIService->generateAIText($prompt);

        $dataInfo = [
            'marketing_images' => $marketing_images,
            'square_marketing_images' => $square_marketing_images,
            'headlines' => $headlines,
            'long_headline' => new AdTextAsset(['text' => $long_headline]),
            'descriptions' => $descriptions,
            'business_name' => $business_name
        ];

        // Creates the responsive display ad info object.
        $responsiveDisplayAdInfo = new ResponsiveDisplayAdInfo($dataInfo);

        // Creates a new ad group ad.
        $adGroupAd = new AdGroupAd([
            'ad' => new Ad([
                'responsive_display_ad' => $responsiveDisplayAdInfo,
                'final_urls' => [$googleAICampaign->final_url]
            ]),
            'status' => $status,
            'ad_group' => $adGroupResourceName
        ]);

        // Creates an ad group ad operation.
        $adGroupAdOperation = new AdGroupAdOperation();
        $adGroupAdOperation->setCreate($adGroupAd);

        // Issues a mutate request to add the ad group ad.
        $adGroupAdServiceClient = $this->googleAdsClient->getAdGroupAdServiceClient();
        $response = $adGroupAdServiceClient->mutateAdGroupAds($customerId, [$adGroupAdOperation]);

        /** @var AdGroupAd $addedAdGroupAd */
        $addedAdGroupAd = $response->getResults()[0];

        $adId = explode("~", $addedAdGroupAd->getResourceName())[1];
        
        return $adId;
    }

    public function createSearchAd(
        GoogleAICampaign $googleAICampaign, 
        int $adGroupId
    ) {
        $customerId = $googleAICampaign->customer->customer_id;
        $title = $googleAICampaign->title;
        $status = CampaignStatus::ENABLED;

        $openAIService = OpenAIService::resolve();

        $prompt = $title." - generate 3 headlines without special characters, limit 20 characters";
        $text = $openAIService->generateAIText($prompt);
        $headlines = [];
        foreach (explode("\n", $text) as $headline) {
            $headlines[] = self::createAdTextAsset($headline);
        }

        $prompt = $title." - generate 2 descriptions without special characters, limit 80 characters";
        $text = $openAIService->generateAIText($prompt);
        $descriptions = [];
        foreach (explode("\n", $text) as $description) {
            $descriptions[] = self::createAdTextAsset($description);
        }

        $ad = new Ad([
            'responsive_search_ad' => new ResponsiveSearchAdInfo([
                'headlines' => $headlines,
                'descriptions' => $descriptions
            ]),
            'final_urls' => [$googleAICampaign->final_url]
        ]);

        // Creates an ad group ad to hold the above ad.
        $adGroupAd = new AdGroupAd([
            'ad_group' => ResourceNames::forAdGroup($customerId, $adGroupId),
            'status' => $status,
            'ad' => $ad
        ]);

        // Creates an ad group ad operation.
        $adGroupAdOperation = new AdGroupAdOperation();
        $adGroupAdOperation->setCreate($adGroupAd);

        // Issues a mutate request to add the ad group ad.
        $adGroupAdServiceClient = $this->googleAdsClient->getAdGroupAdServiceClient();
        $response = $adGroupAdServiceClient->mutateAdGroupAds($customerId, [$adGroupAdOperation]);

        $addedAdGroupAd = $response->getResults()[0];
        
        $adId = explode("~", $addedAdGroupAd->getResourceName())[1];
        
        return $adId;
    }
    
    private function addCampaignBudget($customerId, $v)
    {
        $budget = new CampaignBudget([
            'delivery_method' => BudgetDeliveryMethod::STANDARD,
            'amount_micros' => $v * 1000000,
            'explicitly_shared' => false
        ]);

        $campaignBudgetOperation = new CampaignBudgetOperation();
        $campaignBudgetOperation->setCreate($budget);

        $campaignBudgetServiceClient = $this->googleAdsClient->getCampaignBudgetServiceClient();
        $response = $campaignBudgetServiceClient->mutateCampaignBudgets(
            $customerId,
            [$campaignBudgetOperation]
        );

        $addedBudget = $response->getResults()[0];

        return $addedBudget->getResourceName();
    }

    private function setCampaignTargetingCriteria(
        int $customerId,
        string $campaignResourceName,
        array $locationIds
    ) {
        if (count($locationIds) == 0) return;

        $campaignCriterionOperations = [];

        // Creates the location campaign criteria.
        // Besides using location_id, you can also search by location names from
        // GeoTargetConstantService.suggestGeoTargetConstants() and directly
        // apply GeoTargetConstant.resource_name here. An example can be found
        // in GetGeoTargetConstantByNames.php.
        foreach ($locationIds as $locationId) {
            // Creates a campaign criterion.
            $campaignCriterion = new CampaignCriterion([
                'campaign' => $campaignResourceName,
                'location' => new LocationInfo([
                    'geo_target_constant' => ResourceNames::forGeoTargetConstant($locationId)
                ])
            ]);

            // Creates a campaign criterion operation.
            $campaignCriterionOperation = new CampaignCriterionOperation();
            $campaignCriterionOperation->setCreate($campaignCriterion);

            $campaignCriterionOperations[] = $campaignCriterionOperation;
        }

        // Submits the criteria operations and prints their information.
        $campaignCriterionServiceClient = $this->googleAdsClient->getCampaignCriterionServiceClient();
        $response = $campaignCriterionServiceClient->mutateCampaignCriteria(
            $customerId,
            $campaignCriterionOperations
        );
    }

    private static function uploadAsset(
        GoogleAdsClient $googleAdsClient,
        int $customerId,
        array $img
    ) {
        $assetOperations = [];

        $asset = new Asset([
            'name' => $img['name'],
            'type' => AssetType::IMAGE,
            'image_asset' => new ImageAsset(['data' => file_get_contents($img['path'])])
        ]);

        // Creates an asset operation.
        $assetOperation = new AssetOperation();
        $assetOperation->setCreate($asset);

        $assetOperations[] = $assetOperation;
        
        // Issues a mutate request to add the asset.
        $assetServiceClient = $googleAdsClient->getAssetServiceClient();
        $response = $assetServiceClient->mutateAssets($customerId, $assetOperations);

        $addedImageAssets = [];

        foreach ($response->getResults() as $addedImageAsset) {
            $addedImageAssets[] = new AdImageAsset(
                ['asset' => $addedImageAsset->getResourceName()]
            );
        }

        return $addedImageAssets;
    }

    private static function createAdTextAsset(string $text, int $pinField = null): AdTextAsset
    {
        $adTextAsset = new AdTextAsset(['text' => $text]);
        if (!is_null($pinField)) {
            $adTextAsset->setPinnedField($pinField);
        }
        return $adTextAsset;
    }

    public function updateStatus(GoogleAICampaign $googleAICampaign) 
    {
        $customerId = $googleAICampaign->customer->customer_id;
        $status = $googleAICampaign->status;

        //campaign
        $campaignId = $googleAICampaign->campaign_id;
        $campaign = new Campaign([
            'resource_name' => ResourceNames::forCampaign($customerId, $campaignId),
            'status' => $status
        ]);

        $campaignOperation = new CampaignOperation();
        $campaignOperation->setUpdate($campaign);
        $campaignOperation->setUpdateMask(FieldMasks::allSetFieldsOf($campaign));

        $campaignServiceClient = $this->googleAdsClient->getCampaignServiceClient();
        $response = $campaignServiceClient->mutateCampaigns(
            $customerId,
            [$campaignOperation]
        );
        $updatedCampaign = $response->getResults()[0];

        //adgroup
        $adGroupId = $googleAICampaign->adgroup_id;
        $adGroup = new AdGroup([
            'resource_name' => ResourceNames::forAdGroup($customerId, $adGroupId),
            'status' => $status
        ]);

        $adGroupOperation = new AdGroupOperation();
        $adGroupOperation->setUpdate($adGroup);
        $adGroupOperation->setUpdateMask(FieldMasks::allSetFieldsOf($adGroup));

        $adGroupServiceClient = $this->googleAdsClient->getAdGroupServiceClient();
        $response = $adGroupServiceClient->mutateAdGroups(
            $customerId,
            [$adGroupOperation]
        );
        $updatedAdGroup = $response->getResults()[0];

        //ad
        $adId = $googleAICampaign->ad_id;
        $adGroupAdResourceName = ResourceNames::forAdGroupAd($customerId, $adGroupId, $adId);

        $adGroupAd = new AdGroupAd();
        $adGroupAd->setResourceName($adGroupAdResourceName);
        $adGroupAd->setStatus($status);

        $adGroupAdOperation = new AdGroupAdOperation();
        $adGroupAdOperation->setUpdate($adGroupAd);
        $adGroupAdOperation->setUpdateMask(FieldMasks::allSetFieldsOf($adGroupAd));

        $adGroupAdServiceClient = $this->googleAdsClient->getAdGroupAdServiceClient();
        $response = $adGroupAdServiceClient->mutateAdGroupAds(
            $customerId,
            [$adGroupAdOperation]
        );
        $updatedAdGroupAd = $response->getResults()[0];

        return sprintf(
            "Updated status with resource name: '%s', '%s', '%s'",
            $updatedCampaign->getResourceName(),
            $updatedAdGroup->getResourceName(),
            $updatedAdGroupAd->getResourceName()
        );
    }

    public function test(GoogleAICampaign $googleAICampaign)
    {
        $customerId = $googleAICampaign->customer->customer_id;
        $campaignId = $googleAICampaign->campaign_id;
        
        $googleAdsServiceClient = $this->googleAdsClient->getGoogleAdsServiceClient();
        // Creates a query that retrieves all the disapproved ads of the specified campaign ID.
        $query = 'SELECT ad_group_ad.ad.id, '
                  . 'ad_group_ad.ad.type, '
                  . 'ad_group_ad.policy_summary.approval_status, '
                  . 'ad_group_ad.policy_summary.policy_topic_entries '
                  . 'FROM ad_group_ad '
                  . 'WHERE campaign.id = ' . $campaignId . ' '
                  . 'AND ad_group_ad.policy_summary.approval_status = DISAPPROVED';

        // Issues a search request by specifying page size.
        $response = $googleAdsServiceClient->search($customerId, $query);

        // Iterates over all rows in all pages and counts disapproved ads.
        foreach ($response->iterateAllElements() as $googleAdsRow) {
            /** @var GoogleAdsRow $googleAdsRow */
            $adGroupAd = $googleAdsRow->getAdGroupAd();
            $policySummary = $adGroupAd->getPolicySummary();
            $ad = $adGroupAd->getAd();

            printf(
                "Ad with ID %d and type '%s' was disapproved with the following policy "
                . "topic entries:%s",
                $ad->getId(),
                AdType::name($ad->getType()),
                PHP_EOL
            );
            foreach ($policySummary->getPolicyTopicEntries() as $policyTopicEntry) {
                printf(
                    "  topic: '%s', type: '%s'%s",
                    $policyTopicEntry->getTopic(),
                    PolicyTopicEntryType::name($policyTopicEntry->getType()),
                    PHP_EOL
                );
                foreach ($policyTopicEntry->getEvidences() as $evidence) {
                    $textList = $evidence->getTextList();
                    if (!empty($textList)) {
                        for ($i = 0; $i < $textList->getTexts()->count(); $i++) {
                            printf(
                                "    evidence text[%d]: '%s'%s",
                                $i,
                                $textList->getTexts()[$i],
                                PHP_EOL
                            );
                        }
                    }
                }
            }
        }
        
        return printf(
            "Number of disapproved ads found: %d.%s",
            $response->getPage()->getResponseObject()->getTotalResultsCount(),
            PHP_EOL
        );

    }
}
