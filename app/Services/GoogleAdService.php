<?php

namespace App\Services;

use DateTime;
use Google\Ads\GoogleAds\Lib\V15\GoogleAdsClient;
use Google\Ads\GoogleAds\V15\Common\AdImageAsset;
use Google\Ads\GoogleAds\V15\Common\AdTextAsset;
use Google\Ads\GoogleAds\V15\Common\ImageAsset;
use Google\Ads\GoogleAds\V15\Common\ManualCpc;
use Google\Ads\GoogleAds\V15\Common\ResponsiveDisplayAdInfo;
use Google\Ads\GoogleAds\V15\Common\ResponsiveSearchAdInfo;
use Google\Ads\GoogleAds\V15\Enums\DisplayAdFormatSettingEnum\DisplayAdFormatSetting;
use Google\Ads\GoogleAds\V15\Resources\Ad;
use Google\Ads\GoogleAds\V15\Resources\AdGroupAd;
use Google\Ads\GoogleAds\V15\Services\AdGroupAdOperation;
use Google\Ads\GoogleAds\V15\Resources\Asset;
use Google\Ads\GoogleAds\V15\Enums\AssetTypeEnum\AssetType;
use Google\Ads\GoogleAds\V15\Services\AssetOperation;
use Google\Ads\GoogleAds\Util\V15\ResourceNames;
use Google\Ads\GoogleAds\V15\Enums\AdGroupAdStatusEnum\AdGroupAdStatus;
use Google\Ads\GoogleAds\Util\FieldMasks;
use Google\Ads\GoogleAds\V15\Services\AdOperation;
use Google\Ads\GoogleAds\V15\Enums\AdTypeEnum\AdType;
use Google\Ads\GoogleAds\V15\Enums\ServedAssetFieldTypeEnum\ServedAssetFieldType;
use Google\ApiCore\ApiException;
use App\Models\GoogleAd;

class GoogleAdService extends GoogleService
{
    public function createAd(GoogleAd $googleAd)
    {
        switch ($googleAd->type) {
            case AdType::RESPONSIVE_DISPLAY_AD:
                return $this->createDisplayAd(
                    $googleAd->adgroup->campaign->customer->customer_id,
                    $googleAd->adgroup->gg_adgroup_id,
                    $googleAd->status,
                    $googleAd->data
                );
                break;
            case AdType::RESPONSIVE_SEARCH_AD:
                return $this->createSearchAd(
                    $googleAd->adgroup->campaign->customer->customer_id,
                    $googleAd->adgroup->gg_adgroup_id,
                    $googleAd->status,
                    $googleAd->data
                );
                break;
            default:
                break;
        }
    }

    private function createDisplayAd(
        int $customerId, 
        int $adGroupId, 
        int $status, 
        array $data
    ) {
        $adGroupResourceName = ResourceNames::forAdGroup($customerId, $adGroupId);

        $headlines = [];
        foreach ($data['headlines'] as $headline) {
            $headlines[] = self::createAdTextAsset($headline);
        }

        $descriptions = [];
        foreach ($data['descriptions'] as $description) {
            $descriptions[] = self::createAdTextAsset($description);
        }

        $marketing_images = self::uploadAsset(
            $this->googleAdsClient,
            $customerId,
            $data['images']['marketing_images']
        );
        
        $square_marketing_images = self::uploadAsset(
            $this->googleAdsClient,
            $customerId,
            $data['images']['square_marketing_images']
        );

        $dataInfo = [
            'marketing_images' => $marketing_images,
            'square_marketing_images' => $square_marketing_images,
            'headlines' => $headlines,
            'long_headline' => new AdTextAsset(['text' => $data['long_headline']]),
            'descriptions' => $descriptions,
            'business_name' => $data['business_name']
        ];

        if ($data['call_to_action'] != null) {
            $dataInfo['call_to_action_text'] = $data['call_to_action'];
        }

        // Creates the responsive display ad info object.
        $responsiveDisplayAdInfo = new ResponsiveDisplayAdInfo($dataInfo);

        // Creates a new ad group ad.
        $adGroupAd = new AdGroupAd([
            'ad' => new Ad([
                'responsive_display_ad' => $responsiveDisplayAdInfo,
                'final_urls' => [$data['final_urls']]
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

    private function createSearchAd(
        int $customerId, 
        int $adGroupId, 
        int $status,
        array $data
    ) {
        $headlines = [];
        foreach ($data['headlines'] as $headline) {
            $headlines[] = self::createAdTextAsset($headline);
        }

        $descriptions = [];
        foreach ($data['descriptions'] as $description) {
            $descriptions[] = self::createAdTextAsset($description);
        }

        $ad = new Ad([
            'responsive_search_ad' => new ResponsiveSearchAdInfo([
                'headlines' => $headlines,
                'descriptions' => $descriptions,
                'path1' => $data['path1'],
                'path2' => $data['path2']
            ]),
            'final_urls' => [$data['final_urls']]
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

    public function updateAd(GoogleAd $googleAd)
    {
        switch ($googleAd->type) {
            case AdType::RESPONSIVE_DISPLAY_AD:
                return $this->updateDisplayAd(
                    $googleAd->adgroup->campaign->customer->customer_id,
                    $googleAd->adgroup->gg_adgroup_id,
                    $googleAd->gg_ad_id,
                    $googleAd->status,
                    $googleAd->data
                );
                break;
            case AdType::RESPONSIVE_SEARCH_AD:
                return $this->updateSearchAd(
                    $googleAd->adgroup->campaign->customer->customer_id,
                    $googleAd->adgroup->gg_adgroup_id,
                    $googleAd->gg_ad_id,
                    $googleAd->status,
                    $googleAd->data
                );
                break;
            default:
                break;
        }
    }

    private function updateDisplayAd(
        int $customerId, 
        int $adGroupId,
        int $adId, 
        int $status, 
        array $data
    ) {
        $headlines = [];
        foreach ($data['headlines'] as $headline) {
            $headlines[] = self::createAdTextAsset($headline);
        }

        $descriptions = [];
        foreach ($data['descriptions'] as $description) {
            $descriptions[] = self::createAdTextAsset($description);
        }

        $marketing_images = self::uploadAsset(
            $this->googleAdsClient,
            $customerId,
            $data['images']['marketing_images']
        );
        
        $square_marketing_images = self::uploadAsset(
            $this->googleAdsClient,
            $customerId,
            $data['images']['square_marketing_images']
        );

        $dataInfo = [
            'marketing_images' => $marketing_images,
            'square_marketing_images' => $square_marketing_images,
            'headlines' => $headlines,
            'long_headline' => new AdTextAsset(['text' => $data['long_headline']]),
            'descriptions' => $descriptions,
            'business_name' => $data['business_name']
        ];

        if ($data['call_to_action'] != null) {
            $dataInfo['call_to_action_text'] = $data['call_to_action'];
        }

        $ad = new Ad([
            'resource_name' => ResourceNames::forAd($customerId, $adId),
            'responsive_display_ad' => new ResponsiveDisplayAdInfo($dataInfo),
            'final_urls' => [$data['final_urls']]
        ]);

        // Constructs an operation that will update the ad, using the FieldMasks to derive the
        // update mask. This mask tells the Google Ads API which attributes of the ad you want to
        // change.
        $adOperation = new AdOperation();
        $adOperation->setUpdate($ad);
        $adOperation->setUpdateMask(FieldMasks::allSetFieldsOf($ad));

        // Issues a mutate request to update the ad.
        $adServiceClient = $this->googleAdsClient->getAdServiceClient();
        $response = $adServiceClient->mutateAds($customerId, [$adOperation]);

        $this->updateAdStatus($customerId, $adGroupId, $adId, $status);

        // Prints the resource name of the updated ad.
        /** @var Ad $updatedAd */
        $updatedAd = $response->getResults()[0];

        return sprintf(
            "Updated ad with resource name: '%s'",
            $updatedAd->getResourceName()
        );
    }

    private function updateSearchAd(
        int $customerId, 
        int $adGroupId,
        int $adId, 
        int $status, 
        array $data
    ) {
        $headlines = [];
        foreach ($data['headlines'] as $headline) {
            $headlines[] = self::createAdTextAsset($headline);
        }

        $descriptions = [];
        foreach ($data['descriptions'] as $description) {
            $descriptions[] = self::createAdTextAsset($description);
        }

        $dataInfo = [
            'headlines' => $headlines,
            'descriptions' => $descriptions,
            'path1' => $data['path1'],
            'path2' => $data['path2']
        ];

        $ad = new Ad([
            'resource_name' => ResourceNames::forAd($customerId, $adId),
            'responsive_search_ad' => new ResponsiveSearchAdInfo($dataInfo),
            'final_urls' => [$data['final_urls']]
        ]);

        // Constructs an operation that will update the ad, using the FieldMasks to derive the
        // update mask. This mask tells the Google Ads API which attributes of the ad you want to
        // change.
        $adOperation = new AdOperation();
        $adOperation->setUpdate($ad);
        $adOperation->setUpdateMask(FieldMasks::allSetFieldsOf($ad));

        // Issues a mutate request to update the ad.
        $adServiceClient = $this->googleAdsClient->getAdServiceClient();
        $response = $adServiceClient->mutateAds($customerId, [$adOperation]);

        $this->updateAdStatus($customerId, $adGroupId, $adId, $status);

        // Prints the resource name of the updated ad.
        /** @var Ad $updatedAd */
        $updatedAd = $response->getResults()[0];

        return sprintf(
            "Updated ad with resource name: '%s'",
            $updatedAd->getResourceName()
        );
    }

    public function updateAdStatus(
        int $customerId,
        int $adGroupId,
        int $adId,
        int $status
    ) {
        // Creates ad group ad resource name.
        $adGroupAdResourceName = ResourceNames::forAdGroupAd($customerId, $adGroupId, $adId);

        // Creates an ad and sets its status to PAUSED.
        $adGroupAd = new AdGroupAd();
        $adGroupAd->setResourceName($adGroupAdResourceName);
        $adGroupAd->setStatus($status);

        // Constructs an operation that will pause the ad with the specified resource name,
        // using the FieldMasks utility to derive the update mask. This mask tells the Google Ads
        // API which attributes of the ad group you want to change.
        $adGroupAdOperation = new AdGroupAdOperation();
        $adGroupAdOperation->setUpdate($adGroupAd);
        $adGroupAdOperation->setUpdateMask(FieldMasks::allSetFieldsOf($adGroupAd));

        // Issues a mutate request to pause the ad group ad.
        $adGroupAdServiceClient = $this->googleAdsClient->getAdGroupAdServiceClient();
        $response = $adGroupAdServiceClient->mutateAdGroupAds(
            $customerId,
            [$adGroupAdOperation]
        );

        $updatedAdGroup = $response->getResults()[0];

        return sprintf(
            "Updated ad group ad with resource name: '%s'",
            $updatedAdGroup->getResourceName()
        );
    }

    public function removeAd(
        int $customerId,
        int $adGroupId,
        int $adId
    ) {
        // Creates ad group ad resource name.
        $adGroupAdResourceName = ResourceNames::forAdGroupAd($customerId, $adGroupId, $adId);

        // Constructs an operation that will remove the ad with the specified resource name.
        $adGroupAdOperation = new AdGroupAdOperation();
        $adGroupAdOperation->setRemove($adGroupAdResourceName);

        // Issues a mutate request to remove the ad group ad.
        $adGroupAdServiceClient = $this->googleAdsClient->getAdGroupAdServiceClient();
        $response = $adGroupAdServiceClient->mutateAdGroupAds(
            $customerId,
            [$adGroupAdOperation]
        );

        // Prints the resource name of the removed ad group ad.
        /** @var AdGroupAd $removedAdGroupAd */
        $removedAdGroupAd = $response->getResults()[0];

        return sprintf(
            "Removed ad group ad with resource name: '%s'",
            $removedAdGroupAd->getResourceName()
        );
    }

    private static function uploadAsset(
        GoogleAdsClient $googleAdsClient,
        int $customerId,
        array $images
    ) {
        $assetOperations = [];

        foreach ($images as $image) {
            // Creates an asset.
            $asset = new Asset([
                'name' => $image['title'],
                'type' => AssetType::IMAGE,
                'image_asset' => new ImageAsset(['data' => file_get_contents($image['url'])])
            ]);

            // Creates an asset operation.
            $assetOperation = new AssetOperation();
            $assetOperation->setCreate($asset);

            $assetOperations[] = $assetOperation;
        }
        
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
}
