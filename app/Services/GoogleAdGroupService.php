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
use Google\Ads\GoogleAds\V15\Errors\GoogleAdsError;
use Google\Ads\GoogleAds\Lib\V15\GoogleAdsException;
use Google\Ads\GoogleAds\V15\Common\AdTextAsset;
use Google\Ads\GoogleAds\V15\Common\ResponsiveSearchAdInfo;
use Google\Ads\GoogleAds\V15\Enums\AdGroupAdStatusEnum\AdGroupAdStatus;
use Google\Ads\GoogleAds\V15\Enums\ServedAssetFieldTypeEnum\ServedAssetFieldType;
use Google\Ads\GoogleAds\V15\Resources\Ad;
use Google\Ads\GoogleAds\V15\Resources\AdGroupAd;
use Google\Ads\GoogleAds\V15\Services\AdGroupAdOperation;
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
use Google\Ads\GoogleAds\V15\Common\KeywordInfo;
use Google\Ads\GoogleAds\V15\Enums\KeywordMatchTypeEnum\KeywordMatchType;
use Google\ApiCore\ApiException;
use DateTime;
use App\Models\GoogleAdgroup;

class GoogleAdGroupService extends GoogleService
{
    public function createAdGroup(GoogleAdgroup $googleAdgroup)
    {
        switch ($googleAdgroup->type) {
            case AdGroupType::DISPLAY_STANDARD:
                return $this->createDisplayAdGroup(
                    $googleAdgroup->campaign->customer->customer_id,
                    $googleAdgroup->campaign->gg_campaign_id,
                    $googleAdgroup->title,
                    $googleAdgroup->bid,
                    $googleAdgroup->status
                );
                break;
            case AdGroupType::SEARCH_STANDARD:
                return $this->createSearchAdGroup(
                    $googleAdgroup->campaign->customer->customer_id,
                    $googleAdgroup->campaign->gg_campaign_id,
                    $googleAdgroup->title,
                    $googleAdgroup->bid,
                    $googleAdgroup->status,
                    $googleAdgroup->data
                );
                break;
            default:
                break;
        }
    }

    private function createDisplayAdGroup(
        int $customerId, 
        int $campaignId, 
        string $grpname, 
        float $bid,
        int $status
    ) {
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

    private function createSearchAdGroup(
        int $customerId, 
        int $campaignId, 
        string $grpname, 
        float $bid,
        int $status,
        array $data
    )
    {
        $campaignResourceName = ResourceNames::forCampaign($customerId, $campaignId);

        $adGroup = new AdGroup([
            'name' => $grpname,
            'campaign' => $campaignResourceName,
            'status' => $status,
            'type' => AdGroupType::SEARCH_STANDARD,
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

        if (isset($data)) {
            $this->addKeywords($customerId, $adGroupId, $data);
        }

        return $adGroupId;
    }

    public function updateAdGroup(GoogleAdgroup $googleAdgroup)
    {
        switch ($googleAdgroup->type) {
            case AdGroupType::DISPLAY_STANDARD:
                return $this->updateDisplayAdGroup(
                    $googleAdgroup->campaign->customer->customer_id,
                    $googleAdgroup->gg_adgroup_id,
                    $googleAdgroup->title,
                    $googleAdgroup->bid,
                    $googleAdgroup->status
                );
                break;
            case AdGroupType::SEARCH_STANDARD:
                return $this->updateSearchAdGroup(
                    $googleAdgroup->campaign->customer->customer_id,
                    $googleAdgroup->gg_adgroup_id,
                    $googleAdgroup->title,
                    $googleAdgroup->bid,
                    $googleAdgroup->status,
                    $googleAdgroup->data
                );
                break;
            default:
                break;
        }
    }

    private function updateDisplayAdGroup(
        int $customerId, 
        int $adGroupId, 
        string $grpname, 
        float $bid,
        int $status
    ) {
        $adGroup = new AdGroup([
            'resource_name' => ResourceNames::forAdGroup($customerId, $adGroupId),
            'name' => $grpname,
            'status' => $status,
            'cpc_bid_micros' => $bid * 1000000
        ]);

        $adGroupOperation = new AdGroupOperation();
        $adGroupOperation->setUpdate($adGroup);
        $adGroupOperation->setUpdateMask(FieldMasks::allSetFieldsOf($adGroup));

        // Issues a mutate request to update the ad group.
        $adGroupServiceClient = $this->googleAdsClient->getAdGroupServiceClient();
        $response = $adGroupServiceClient->mutateAdGroups(
            $customerId,
            [$adGroupOperation]
        );

        // Prints the resource name of the updated ad group.
        /** @var AdGroup $updatedAdGroup */
        $updatedAdGroup = $response->getResults()[0];
        
        return sprintf(
            "Updated ad group with resource name: '%s'",
            $updatedAdGroup->getResourceName()
        );
    }

    private function updateSearchAdGroup(
        int $customerId, 
        int $adGroupId, 
        string $grpname, 
        float $bid,
        int $status,
        array $data
    ) {
        $adGroup = new AdGroup([
            'resource_name' => ResourceNames::forAdGroup($customerId, $adGroupId),
            'name' => $grpname,
            'status' => $status,
            'cpc_bid_micros' => $bid * 1000000
        ]);

        $adGroupOperation = new AdGroupOperation();
        $adGroupOperation->setUpdate($adGroup);
        $adGroupOperation->setUpdateMask(FieldMasks::allSetFieldsOf($adGroup));

        // Issues a mutate request to update the ad group.
        $adGroupServiceClient = $this->googleAdsClient->getAdGroupServiceClient();
        $response = $adGroupServiceClient->mutateAdGroups(
            $customerId,
            [$adGroupOperation]
        );

        if (isset($data)) {
            $this->addKeywords($customerId, $adGroupId, $data);
        }

        // Prints the resource name of the updated ad group.
        /** @var AdGroup $updatedAdGroup */
        $updatedAdGroup = $response->getResults()[0];
        
        return sprintf(
            "Updated ad group with resource name: '%s'",
            $updatedAdGroup->getResourceName()
        );
    }

    public function updateAdGroupStatus(
        int $customerId,
        int $adGroupId,
        int $status
    ) {
        // Creates an ad group object with the specified resource name and other changes.
        $adGroup = new AdGroup([
            'resource_name' => ResourceNames::forAdGroup($customerId, $adGroupId),
            'status' => $status
        ]);

        // Constructs an operation that will update the ad group with the specified resource name,
        // using the FieldMasks utility to derive the update mask. This mask tells the Google Ads
        // API which attributes of the ad group you want to change.
        $adGroupOperation = new AdGroupOperation();
        $adGroupOperation->setUpdate($adGroup);
        $adGroupOperation->setUpdateMask(FieldMasks::allSetFieldsOf($adGroup));

        // Issues a mutate request to update the ad group.
        $adGroupServiceClient = $this->googleAdsClient->getAdGroupServiceClient();
        $response = $adGroupServiceClient->mutateAdGroups(
            $customerId,
            [$adGroupOperation]
        );

        // Prints the resource name of the updated ad group.
        /** @var AdGroup $updatedAdGroup */
        $updatedAdGroup = $response->getResults()[0];
        
        return sprintf(
            "Updated ad group with resource name: '%s'",
            $updatedAdGroup->getResourceName()
        );
    }

    public function removeAdGroup(
        int $customerId,
        int $adGroupId
    ) {
        // Creates ad group resource name.
        $adGroupResourceName = ResourceNames::forAdGroup($customerId, $adGroupId);

        // Constructs an operation that will remove the ad group with the specified resource name.
        $adGroupOperation = new AdGroupOperation();
        $adGroupOperation->setRemove($adGroupResourceName);

        // Issues a mutate request to remove the ad group.
        $adGroupServiceClient = $this->googleAdsClient->getAdGroupServiceClient();
        $response = $adGroupServiceClient->mutateAdGroups(
            $customerId,
            [$adGroupOperation]
        );

        // Prints the resource name of the removed ad group.
        /** @var AdGroup $removedAdGroup */
        $removedAdGroup = $response->getResults()[0];
        
        return sprintf(
            "Removed ad group with resource name: '%s'",
            $removedAdGroup->getResourceName()
        );
    }

    private function addKeywords(
        int $customerId,
        int $adGroupId,
        array $data
    ) {
        $adGroupCriterionOperations = [];

        foreach ($data['keyword']['broad'] as $item) {
            if (isset($item) && !empty(trim($item))) {
                $keywordInfo = new KeywordInfo([
                    'text' => $item,
                    'match_type' => KeywordMatchType::BROAD
                ]);

                $adGroupCriterion = new AdGroupCriterion([
                    'ad_group' => ResourceNames::forAdGroup($customerId, $adGroupId),
                    'status' => AdGroupCriterionStatus::ENABLED,
                    'keyword' => $keywordInfo
                ]);

                $adGroupCriterionOperation = new AdGroupCriterionOperation();
                $adGroupCriterionOperation->setCreate($adGroupCriterion);
                $adGroupCriterionOperations[] = $adGroupCriterionOperation;
            }
        }

        foreach ($data['keyword']['phrase'] as $item) {
            if (isset($item) && !empty(trim($item))) {
                $keywordInfo = new KeywordInfo([
                    'text' => $item,
                    'match_type' => KeywordMatchType::PHRASE
                ]);

                $adGroupCriterion = new AdGroupCriterion([
                    'ad_group' => ResourceNames::forAdGroup($customerId, $adGroupId),
                    'status' => AdGroupCriterionStatus::ENABLED,
                    'keyword' => $keywordInfo
                ]);

                $adGroupCriterionOperation = new AdGroupCriterionOperation();
                $adGroupCriterionOperation->setCreate($adGroupCriterion);
                $adGroupCriterionOperations[] = $adGroupCriterionOperation;
            }
        }

        foreach ($data['keyword']['exact'] as $item) {
            if (isset($item) && !empty(trim($item))) {
                $keywordInfo = new KeywordInfo([
                    'text' => $item,
                    'match_type' => KeywordMatchType::EXACT
                ]);

                $adGroupCriterion = new AdGroupCriterion([
                    'ad_group' => ResourceNames::forAdGroup($customerId, $adGroupId),
                    'status' => AdGroupCriterionStatus::ENABLED,
                    'keyword' => $keywordInfo
                ]);

                $adGroupCriterionOperation = new AdGroupCriterionOperation();
                $adGroupCriterionOperation->setCreate($adGroupCriterion);
                $adGroupCriterionOperations[] = $adGroupCriterionOperation;
            }
        }

        if (count($adGroupCriterionOperations) > 0) {
            // Issues a mutate request to add the ad group criterion.
            $adGroupCriterionServiceClient = $this->googleAdsClient->getAdGroupCriterionServiceClient();
            $response = $adGroupCriterionServiceClient->mutateAdGroupCriteria(
                $customerId,
                $adGroupCriterionOperations
            );
        }

        //printf("Added %d ad group criteria:%s", $response->getResults()->count(), PHP_EOL);
    }

    public function updateAdGroupAdStatus(
        int $customerId,
        int $adGroupId,
        array $adIds,
        int $status
    ) {
        $adGroupAdOperations = [];

        foreach ($adIds as $adId) {
            $adGroupAdResourceName = ResourceNames::forAdGroupAd($customerId, $adGroupId, $adId);

            $adGroupAd = new AdGroupAd();
            $adGroupAd->setResourceName($adGroupAdResourceName);
            $adGroupAd->setStatus($status);

            $adGroupAdOperation = new AdGroupAdOperation();
            $adGroupAdOperation->setUpdate($adGroupAd);
            $adGroupAdOperation->setUpdateMask(FieldMasks::allSetFieldsOf($adGroupAd));

            $adGroupAdOperations[] = $adGroupAdOperation;
        }

        $adGroupAdServiceClient = $this->googleAdsClient->getAdGroupAdServiceClient();
        $response = $adGroupAdServiceClient->mutateAdGroupAds(
            $customerId,
            $adGroupAdOperations
        );

        //$updatedAdGroupAd = $response->getResults()[0];

        $res = "";
        foreach ($response->getResults() as $result) {
            $res .= sprintf(
                "Ad group ad with resource name: '%s' is changed.%s",
                $result->getResourceName(),
                PHP_EOL
            );
        }

        return $res;
    }
}
