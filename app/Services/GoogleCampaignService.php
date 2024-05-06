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
use Google\ApiCore\ApiException;
use DateTime;
use App\Models\GoogleCampaign;

class GoogleCampaignService extends GoogleService
{
    public function createCampaign(GoogleCampaign $googleCampaign)
    {
        switch ($googleCampaign->type) {
            case AdvertisingChannelType::DISPLAY:
                return $this->createDisplayCampaign(
                    $googleCampaign->customer->customer_id,
                    $googleCampaign->title,
                    $googleCampaign->budget,
                    $googleCampaign->location,
                    $googleCampaign->status
                );
                break;
            case AdvertisingChannelType::SEARCH:
                return $this->createSearchCampaign(
                    $googleCampaign->customer->customer_id,
                    $googleCampaign->title,
                    $googleCampaign->budget,
                    $googleCampaign->location,
                    $googleCampaign->status,
                    $googleCampaign->data
                );
                break;
            default:
                break;
        }
    }

    private function createDisplayCampaign(
        int $customerId, 
        string $title, 
        float $budget,
        int $location,
        int $status
    ) {
        $budgetResourceName = $this->addCampaignBudget($customerId, $budget);

        // Creates the campaign.
        $campaign = new Campaign([
            'name' => $title,
            // Dynamic remarketing campaigns are only available on the Google Display Network.
            'advertising_channel_type' => AdvertisingChannelType::DISPLAY,
            'status' => $status,
            'campaign_budget' => $budgetResourceName,
            'manual_cpc' => new ManualCpc(),
            // This connects the campaign to the merchant center account.
            //'shopping_setting' => $shoppingSettings
        ]);

        $campaignOperation = new CampaignOperation();
        $campaignOperation->setCreate($campaign);
        
        $campaignServiceClient = $this->googleAdsClient->getCampaignServiceClient();
        $response = $campaignServiceClient->mutateCampaigns($customerId, [$campaignOperation]);

        $createdCampaignResourceName = $response->getResults()[0]->getResourceName();

        $locationIds = [];

        if ($location == 2) {
            $locationIds = [
                2840,   // USA
                2124   // Canada
            ];
        } else if ($location == 3) {
            $locationIds = [
                2840
            ];
        }

        $this->setCampaignTargetingCriteria($customerId, $createdCampaignResourceName, $locationIds);

        $campaignId = explode("/", $createdCampaignResourceName)[3];
        
        return $campaignId;
    }

    private function createSearchCampaign(
        int $customerId, 
        string $title, 
        float $budget,
        int $location,
        int $status,
        array $data
    )
    {
        // Creates a single shared budget to be used by the campaigns added below.
        $budgetResourceName = $this->addCampaignBudget($customerId, $budget);

        $search_network = false;
        $content_network = false;

        if ($data && in_array("search", $data['network']))
            $search_network = true;
        
        if ($data && in_array("display", $data['network']))
            $content_network = true;

        // Configures the campaign network options.
        $networkSettings = new NetworkSettings([
            'target_google_search' => true,
            'target_search_network' => $search_network,
            // Enables Display Expansion on Search campaigns. See
            // https://support.google.com/google-ads/answer/7193800 to learn more.
            'target_content_network' => $content_network,
            'target_partner_search_network' => false
        ]);

        $campaign = new Campaign([
            'name' => $title,
            'advertising_channel_type' => AdvertisingChannelType::SEARCH,
            // Recommendation: Set the campaign to PAUSED when creating it to prevent
            // the ads from immediately serving. Set to ENABLED once you've added
            // targeting and the ads are ready to serve.
            'status' => $status,
            // Sets the bidding strategy and budget.
            'manual_cpc' => new ManualCpc(),
            'campaign_budget' => $budgetResourceName,
            // Adds the network settings configured above.
            'network_settings' => $networkSettings,
            // Optional: Sets the start and end dates.
            //'start_date' => date('Ymd', strtotime('+1 day')),
            //'end_date' => date('Ymd', strtotime('+1 month'))
        ]);
        // [END add_campaigns_1]

        // Creates a campaign operation.
        $campaignOperation = new CampaignOperation();
        $campaignOperation->setCreate($campaign);

        // Issues a mutate request to add campaigns.
        $campaignServiceClient = $this->googleAdsClient->getCampaignServiceClient();
        $response = $campaignServiceClient->mutateCampaigns($customerId, [$campaignOperation]);

        $createdCampaignResourceName = $response->getResults()[0]->getResourceName();

        $locationIds = [];

        if ($location == 2) {
            $locationIds = [
                2840,   // USA
                2124   // Canada
            ];
        } else if ($location == 3) {
            $locationIds = [
                2840
            ];
        }

        $this->setCampaignTargetingCriteria($customerId, $createdCampaignResourceName, $locationIds);

        $campaignId = explode("/", $createdCampaignResourceName)[3];
        
        return $campaignId;
    }

    public function updateCampaign(GoogleCampaign $googleCampaign)
    {
        switch ($googleCampaign->type) {
            case AdvertisingChannelType::DISPLAY:
                return $this->updateDisplayCampaign(
                    $googleCampaign->customer->customer_id,
                    $googleCampaign->gg_campaign_id,
                    $googleCampaign->title,
                    $googleCampaign->budget,
                    $googleCampaign->location,
                    $googleCampaign->status
                );
                break;
            case AdvertisingChannelType::SEARCH:
                return $this->updateSearchCampaign(
                    $googleCampaign->customer->customer_id,
                    $googleCampaign->gg_campaign_id,
                    $googleCampaign->title,
                    $googleCampaign->budget,
                    $googleCampaign->location,
                    $googleCampaign->status,
                    $googleCampaign->data
                );
                break;
            default:
                break;
        }
    }

    private function updateDisplayCampaign(
        int $customerId, 
        int $campaignId,
        string $title, 
        float $budget,
        int $location,
        int $status
    ) {
        $budgetResourceName = $this->addCampaignBudget($customerId, $budget);

        // Creates a campaign object with the specified resource name and other changes.
        $campaign = new Campaign([
            'resource_name' => ResourceNames::forCampaign($customerId, $campaignId),
            'name' => $title,
            'status' => $status,
            'campaign_budget' => $budgetResourceName
        ]);

        // Constructs an operation that will update the campaign with the specified resource name,
        // using the FieldMasks utility to derive the update mask. This mask tells the Google Ads
        // API which attributes of the campaign you want to change.
        $campaignOperation = new CampaignOperation();
        $campaignOperation->setUpdate($campaign);
        $campaignOperation->setUpdateMask(FieldMasks::allSetFieldsOf($campaign));

        // Issues a mutate request to update the campaign.
        $campaignServiceClient = $this->googleAdsClient->getCampaignServiceClient();
        $response = $campaignServiceClient->mutateCampaigns(
            $customerId,
            [$campaignOperation]
        );

        $updatedCampaignResourceName = $response->getResults()[0]->getResourceName();

        $locationIds = [];

        if ($location == 2) {
            $locationIds = [
                2840,   // USA
                2124   // Canada
            ];
        } else if ($location == 3) {
            $locationIds = [
                2840
            ];
        }

        $this->setCampaignTargetingCriteria($customerId, $updatedCampaignResourceName, $locationIds);

        // Prints the resource name of the updated campaign.
        /** @var Campaign $updatedCampaign */
        $updatedCampaign = $response->getResults()[0];

        return sprintf(
            "Updated campaign with resource name: '%s'",
            $updatedCampaign->getResourceName()
        );
    }

    private function updateSearchCampaign(
        int $customerId, 
        int $campaignId,
        string $title, 
        float $budget,
        int $location,
        int $status,
        array $data
    ) {
        $budgetResourceName = $this->addCampaignBudget($customerId, $budget);

        $search_network = false;
        $content_network = false;

        if ($data && in_array("search", $data['network']))
            $search_network = true;
        
        if ($data && in_array("display", $data['network']))
            $content_network = true;

        $networkSettings = new NetworkSettings([
            'target_google_search' => true,
            'target_search_network' => $search_network,
            'target_content_network' => $content_network,
            'target_partner_search_network' => false
        ]);

        // Creates a campaign object with the specified resource name and other changes.
        $campaign = new Campaign([
            'resource_name' => ResourceNames::forCampaign($customerId, $campaignId),
            'name' => $title,
            'status' => $status,
            'campaign_budget' => $budgetResourceName,
            'network_settings' => $networkSettings
        ]);

        // Constructs an operation that will update the campaign with the specified resource name,
        // using the FieldMasks utility to derive the update mask. This mask tells the Google Ads
        // API which attributes of the campaign you want to change.
        $campaignOperation = new CampaignOperation();
        $campaignOperation->setUpdate($campaign);
        $campaignOperation->setUpdateMask(FieldMasks::allSetFieldsOf($campaign));

        // Issues a mutate request to update the campaign.
        $campaignServiceClient = $this->googleAdsClient->getCampaignServiceClient();
        $response = $campaignServiceClient->mutateCampaigns(
            $customerId,
            [$campaignOperation]
        );

        $updatedCampaignResourceName = $response->getResults()[0]->getResourceName();

        $locationIds = [];

        if ($location == 2) {
            $locationIds = [
                2840,   // USA
                2124   // Canada
            ];
        } else if ($location == 3) {
            $locationIds = [
                2840
            ];
        }

        $this->setCampaignTargetingCriteria($customerId, $updatedCampaignResourceName, $locationIds);

        // Prints the resource name of the updated campaign.
        /** @var Campaign $updatedCampaign */
        $updatedCampaign = $response->getResults()[0];

        return sprintf(
            "Updated campaign with resource name: '%s'",
            $updatedCampaign->getResourceName()
        );
    }

    public function updateCampaignStatus(
        int $customerId, 
        int $campaignId,
        int $status
    ) {
        // Creates a campaign object with the specified resource name and other changes.
        $campaign = new Campaign([
            'resource_name' => ResourceNames::forCampaign($customerId, $campaignId),
            'status' => $status
        ]);

        // Constructs an operation that will update the campaign with the specified resource name,
        // using the FieldMasks utility to derive the update mask. This mask tells the Google Ads
        // API which attributes of the campaign you want to change.
        $campaignOperation = new CampaignOperation();
        $campaignOperation->setUpdate($campaign);
        $campaignOperation->setUpdateMask(FieldMasks::allSetFieldsOf($campaign));

        // Issues a mutate request to update the campaign.
        $campaignServiceClient = $this->googleAdsClient->getCampaignServiceClient();
        $response = $campaignServiceClient->mutateCampaigns(
            $customerId,
            [$campaignOperation]
        );

        // Prints the resource name of the updated campaign.
        /** @var Campaign $updatedCampaign */
        $updatedCampaign = $response->getResults()[0];

        return sprintf(
            "Updated campaign status with resource name: '%s'",
            $updatedCampaign->getResourceName()
        );
    }

    public function removeCampaign(
        int $customerId,
        int $campaignId
    ) {
        // Creates the resource name of a campaign to remove.
        $campaignResourceName = ResourceNames::forCampaign($customerId, $campaignId);

        // Creates a campaign operation.
        $campaignOperation = new CampaignOperation();
        $campaignOperation->setRemove($campaignResourceName);

        // Issues a mutate request to remove the campaign.
        $campaignServiceClient = $this->googleAdsClient->getCampaignServiceClient();
        $response = $campaignServiceClient->mutateCampaigns($customerId, [$campaignOperation]);

        /** @var Campaign $removedCampaign */
        $removedCampaign = $response->getResults()[0];
        
        return sprintf(
            "Removed campaign with resource name '%s'",
            $removedCampaign->getResourceName()
        );
    }

    private function addCampaignBudget($customerId, $v)
    {
        $budget = new CampaignBudget([
            //'name' => 'budget#' . $this->getPrintableDatetime(),
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

        /*printf(
            "Created %d campaign criteria with resource names:%s",
            $response->getResults()->count(),
            PHP_EOL
        );

        foreach ($response->getResults() as $createdCampaignCriterion) {
            printf("\t%s%s", $createdCampaignCriterion->getResourceName(), PHP_EOL);
        }*/
    }

    public function updateMultipleCampaignsStatus(
        int $customerId, 
        array $campaignIds,
        int $status
    ) {
        $campaignOperations = [];

        foreach ($campaignIds as $campaignId) {            
            $campaign = new Campaign([
                'resource_name' => ResourceNames::forCampaign($customerId, $campaignId),
                'status' => $status
            ]);

            $campaignOperation = new CampaignOperation();
            $campaignOperation->setUpdate($campaign);
            $campaignOperation->setUpdateMask(FieldMasks::allSetFieldsOf($campaign));

            $campaignOperations[] = $campaignOperation;
        }
        
        $campaignServiceClient = $this->googleAdsClient->getCampaignServiceClient();
        $response = $campaignServiceClient->mutateCampaigns(
            $customerId,
            $campaignOperations
        );

        $res = "";
        foreach ($response->getResults() as $result) {
            $res .= sprintf(
                "Campaign with resource name: '%s' is changed.%s",
                $result->getResourceName(),
                PHP_EOL
            );
        }

        return $res;
    }
}
