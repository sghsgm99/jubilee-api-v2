<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\Enums\FacebookCampaignStatusEnum;
use FacebookAds\Object\AdAccount;
use FacebookAds\Object\Campaign;
use FacebookAds\Object\Fields\AdsInsightsFields;
use FacebookAds\Object\Fields\CampaignFields;
use FacebookAds\Object\Values\AdsInsightsDatePresetValues;
use Illuminate\Support\Facades\Http;

class FacebookTestCampaignService extends FacebookService
{
    public function getCampaigns(
        array $status
    )
    {
        $account = new AdAccount($this->act_ad_account_id);
        
        $fields = [
            CampaignFields::OBJECTIVE,
            CampaignFields::NAME,
            CampaignFields::STATUS
        ];
        $params = ['effective_status' => $status];

        return $account->getCampaigns(
            $fields,
            $params
        )->getResponse()->getContent();
    }

    public function getSingleCampaign(int $campaign_id)
    {
        $campaign = new Campaign($campaign_id);
        
        $fields = [
            CampaignFields::OBJECTIVE,
            CampaignFields::NAME,
            CampaignFields::STATUS
        ];
        $params = [];

        return $campaign->getSelf(
            $fields,
            $params
        )->exportAllData();

    }

    public function deleteCampaign($campaign_id)
    {

        $campaign = new Campaign($campaign_id);
        return $campaign->deleteSelf()->getContent();

    }

    public function createCampaign(
        string $name,
        string $objective,
        array $special_ad_category,
        string $ad_account
    )
    {
        $fields = [];
        $params = [
            "name" => $name,
            "objective" => $objective,
            "status" => FacebookCampaignStatusEnum::PAUSED,
            "special_ad_categories" => $special_ad_category,
        ];

        $ad_account = $ad_account ? 'act_'.$ad_account : $this->act_ad_account_id;

        $campaign = (new AdAccount($ad_account))->createCampaign(
            $fields,
            $params
        );

        return ['id' => $campaign->id];

    }

    public function updateCampaign(
        int $campaign_id,
        string $name,
        string $objective,
        string $status,
        array $special_ad_category
    )
    {

        $campaign = new Campaign($campaign_id);

        $fields = [];
        $params = [
            "name" => $name,
            "objective" => $objective,
            "status" => $status,
            // "special_ad_categories" => $special_ad_category,
        ];

        return $campaign->updateSelf($fields, $params)->exportAllData();

    }
    
    public function updateCampaignStatus(
        int $campaign_id,
        string $status
    )
    {

        $campaign = new Campaign($campaign_id);

        $fields = [];
        $params = [
            "status" => $status
        ];

        return $campaign->updateSelf($fields, $params)->exportAllData();

    }

    public function campaignInsights(
        array $ids
    )
    {

        $camp = new Campaign($ids['campaign_id']);

        $params = [
            'date_preset' => AdsInsightsDatePresetValues::MAXIMUM
        ];

        $fields = [
            AdsInsightsFields::CLICKS,
            AdsInsightsFields::SPEND,
            AdsInsightsFields::REACH,
            AdsInsightsFields::IMPRESSIONS,
            AdsInsightsFields::ACCOUNT_ID,
            AdsInsightsFields::DATE_START,
            AdsInsightsFields::DATE_STOP,
        ];

        $api = $camp->getInsights($fields, $params); 
        
        
        return $api->getResponse()->getContent();

    }



}



