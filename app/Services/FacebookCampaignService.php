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

class FacebookCampaignService extends FacebookService
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
        string $name = null,
        string $objective = null,
        string $status = null,
        array $special_ad_category = null
    )
    {

        $campaign = new Campaign($campaign_id);

        $fields = [];
        $params = [];

        if($name) {
            $params['name'] = $name;
        }

        if($objective) {
            $params['obective'] = $objective;
        }

        if($status) {
            $params['status'] = $status;
        }

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

    public function duplicateCampaign(string $campaign_id, bool $get_campaign = false)
    {   
        $url = "{$this->url}{$campaign_id}/copies";

        $api = Http::post($url, [
            'access_token' => $this->user_access_token
        ]);

        if(!$api->ok()) {
            return [
                'error' => true,
                'message' => $api->json()['error']['error_user_msg'] ?? $api->json()['error']['message'],
                'response' => $api->json()
            ];
        }
        
        $campaign = $api->json();

        if($get_campaign) {
            $campaign = $this->getSingleCampaign($api->json()['copied_campaign_id']);
        }

        return $campaign;
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



