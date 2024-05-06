<?php

namespace App\Services;

use App\Models\Enums\FacebookAdSetBidStrategyEnum;
use App\Models\Enums\FacebookAdSetBillingEventEnum;
use App\Models\Enums\FacebookCampaignObjectiveEnum;
use App\Models\Enums\FacebookCampaignStatusEnum;
use App\Models\Enums\FacebookCustomEventTypeEnum;
use FacebookAds\Object\AdAccount;
use FacebookAds\Object\AdSet;
use FacebookAds\Object\Fields\AdSetFields;
use FacebookAds\Object\Values\AdSetBidStrategyValues;
use Illuminate\Support\Facades\Http;

class FacebookAdSetService extends FacebookService
{
    public function getAdSets(
        array $status
    )
    {
        $account = new AdAccount($this->act_ad_account_id);
        $fields = [
            AdSetFields::NAME,
            AdSetFields::BILLING_EVENT,
            AdSetFields::BID_STRATEGY,
            AdSetFields::BID_AMOUNT,
            AdSetFields::START_TIME,
            AdSetFields::END_TIME,
            AdSetFields::DAILY_BUDGET,
            AdSetFields::LIFETIME_BUDGET,
            AdSetFields::TARGETING,
            AdSetFields::STATUS
        ];
        $params = ['effective_status' => $status];
        // $params = [];

        return $account->getAdSets(
            $fields,
            $params
        )->getResponse()->getContent();

    }

    public function getSingleAdSet(int $adset_id)
    {

        $adset = new AdSet($adset_id);
        $fields = [
            AdSetFields::NAME,
            AdSetFields::BILLING_EVENT,
            AdSetFields::BID_STRATEGY,
            AdSetFields::BID_AMOUNT,
            AdSetFields::START_TIME,
            AdSetFields::END_TIME,
            AdSetFields::DAILY_BUDGET,
            AdSetFields::LIFETIME_BUDGET,
            AdSetFields::TARGETING,
            AdSetFields::STATUS
        ];
        $params = [];

        return $adset->getSelf(
            $fields,
            $params
        )->exportAllData();

    }

    public function createAdSet(
        int $campaign_id,
        string $name,
        string $billing_event,
        int $bid_amount,
        string $bid_strategy,
        string $budget_type,
        int $budget_amount,
        string $start_time,
        string $end_time,
        array $targeting,
        string $ad_account,
        string $objective,
        string $pixel_id = null,
        string $custom_event_type = null
    )
    {

        // targeting geo_locations fields formatting
        if(isset($targeting['geo_locations']['locations'])) {
            $geo_locations = $this->getGeoLocations($targeting['geo_locations']['locations']);
            foreach ($geo_locations as $key => $geo_location) {
                if(!isset($targeting['geo_locations'][$key])) {
                    $targeting['geo_locations'][$key] = [];
                }
                $targeting['geo_locations'][$key] = $geo_locations[$key];
            }
            unset($targeting['geo_locations']['locations']);
        }

        // targeting excluded_geo_locations fields formatting
        if(isset($targeting['excluded_geo_locations']['locations'])) {
            $excluded_geo_locations = $this->getGeoLocations($targeting['excluded_geo_locations']['locations']);
            foreach ($excluded_geo_locations as $key => $geo_location) {
                if(!isset($targeting['excluded_geo_locations'][$key])) {
                    $targeting['excluded_geo_locations'][$key] = [];
                }
                $targeting['excluded_geo_locations'][$key] = $excluded_geo_locations[$key];
            }
            unset($targeting['excluded_geo_locations']['locations']);
        }
        

        // targeting demographic fields formatting
        $demographs = [
            'interests',
            'behaviors',
            'life_events',
            'industries',
            'income',
            'family_statuses',
            'user_device',
            'eduation_status',
            'education_schools',
            'education_majors',
            'work_positions',
        ];

        foreach ($demographs as $demograph) {
            if(isset($targeting[$demograph]) && !empty($targeting[$demograph])) {
                $arr = [];
                // check if demographic is user_device
                if($demograph == 'user_device') {
                    $user_os = [];
                    foreach ($targeting[$demograph] as $item) {
                        if(isset($item['name'])) {
                            $arr[] = $item['name'];
                            
                            // check if platform does not exist
                            if(!in_array($item['platform'], $user_os)){
                                $user_os[] = $item['platform'];
                            }
                        }
                    }
                    // add user_os to targeting if has data
                    if(count($user_os) > 0) {
                        $targeting['user_os'] = $user_os;
                    }
                
                }else {
                    foreach ($targeting[$demograph] as $item) {
                        if(isset($item['id'])) {
                            $arr[] = ['id' => $item['id']];
                        }
                    }
                }

                if(count($arr) > 0) {
                    $targeting[$demograph] = $arr;
                }

            }
        }

        // targeting custom audiences
        if(isset($targeting['custom_audience_id']) && !is_null($targeting['custom_audience_id'])) {
            $targeting['custom_audiences'] = [
                ["id" => $targeting['custom_audience_id']]
            ];
        }
        unset($targeting['custom_audience_id']);
        unset($targeting['custom_audience']);
        
        // SDK code base
        $fields = [];
        $params = [
            "name" => $name,
            "billing_event" => $billing_event,
            "bid_strategy" => $bid_strategy,
            $budget_type => $budget_amount * 100,
            // "optimization_goal" => $billing_event,
            "start_time" => $start_time,
            'targeting' => $targeting,
            "campaign_id" => $campaign_id,
            "status" => FacebookCampaignStatusEnum::PAUSED
        ];

        // promoted object
        switch ($objective) {
            case FacebookCampaignObjectiveEnum::CONVERSIONS:
                if($pixel_id)
                $params['promoted_object']['pixel_id'] = $pixel_id;
                
                if($custom_event_type)
                $params['promoted_object']['custom_event_type'] = FacebookCustomEventTypeEnum::memberByValue($custom_event_type)->value;
                
                break;
            
            default:
                $params['promoted_object'] = [
                    "page_id" => $this->page_id
                ];
                break;
        }

        if($end_time) {
            $params['end_time'] = $end_time;
        }
        if($bid_strategy != AdSetBidStrategyValues::LOWEST_COST_WITHOUT_CAP) {
            $params['bid_amount'] = $bid_amount;
        }

        $ad_account = $ad_account ? 'act_'.$ad_account : $this->act_ad_account_id;

        $adset = (new AdAccount($ad_account))->createAdSet(
            $fields,
            $params
        );

        return ['id' => $adset->id];

    }

    public function generateTargeting(array $targeting)
    {
        // targeting geo_locations fields formatting
        if(isset($targeting['geo_locations']['locations'])) {
            $geo_locations = $this->getGeoLocations($targeting['geo_locations']['locations']);
            foreach ($geo_locations as $key => $geo_location) {
                if(!isset($targeting['geo_locations'][$key])) {
                    $targeting['geo_locations'][$key] = [];
                }
                $targeting['geo_locations'][$key] = $geo_locations[$key];
            }
            unset($targeting['geo_locations']['locations']);
        }

        // targeting excluded_geo_locations fields formatting
        if(isset($targeting['excluded_geo_locations']['locations'])) {
            $excluded_geo_locations = $this->getGeoLocations($targeting['excluded_geo_locations']['locations']);
            foreach ($excluded_geo_locations as $key => $geo_location) {
                if(!isset($targeting['excluded_geo_locations'][$key])) {
                    $targeting['excluded_geo_locations'][$key] = [];
                }
                $targeting['excluded_geo_locations'][$key] = $excluded_geo_locations[$key];
            }
            unset($targeting['excluded_geo_locations']['locations']);
        }
        

        // targeting demographic fields formatting
        $demographs = [
            'interests',
            'behaviors',
            'life_events',
            'industries',
            'income',
            'family_statuses',
            'user_device',
            'eduation_status',
            'education_schools',
            'education_majors',
            'work_positions',
        ];

        foreach ($demographs as $demograph) {
            if(isset($targeting[$demograph])) {
                $arr = [];
                // check if demographic is user_device
                if($demograph == 'user_device') {
                    $user_os = [];
                    foreach ($targeting[$demograph] as $item) {
                        if(isset($item['name'])) {
                            $arr[] = $item['name'];
                            
                            // check if platform does not exist
                            if(!in_array($item['platform'], $user_os)){
                                $user_os[] = $item['platform'];
                            }
                        }
                    }
                    // add user_os to targeting if has data
                    if(count($user_os) > 0) {
                        $targeting['user_os'] = $user_os;
                    }
                
                }else {
                    foreach ($targeting[$demograph] as $item) {
                        if(isset($item['id'])) {
                            $arr[] = ['id' => $item['id']];
                        }
                    }
                }

                if(count($arr) > 0) {
                    $targeting[$demograph] = $arr;
                }

            }
        }

        // targeting custom audiences
        if(isset($targeting['custom_audience_id']) && !is_null($targeting['custom_audience_id'])) {
            $targeting['custom_audiences'] = [
                ["id" => $targeting['custom_audience_id']]
            ];
        }
        unset($targeting['custom_audience_id']);
        unset($targeting['custom_audience']);

        return $targeting;
    }

    public function getGeoLocations($locations)
    {
        $geo_locations = [
            'countries' => [],
            'regions' => [],
            'cities' => []
        ];
        foreach ($locations as $location) {
            switch ($location['type']) {
                case 'country':
                    if(!in_array($location['country_code'], $geo_locations['countries'])) {
                        $geo_locations['countries'][] = $location['country_code'];
                    }

                    break;

                case 'region':
                    $exist = 0;
                    foreach ($geo_locations['regions'] as $region) {
                        // check region if do not exist
                        if($region['key'] = $location['region_id']) {
                        $exist = 1;
                        }
                    }
                    if($exist == 0) {
                        $geo_locations['regions'][] = ['key' => $location['region_id']];
                    }

                    break;

                case 'city':
                    $exist = 0;
                    foreach ($geo_locations['cities'] as $city) {
                        // check city if do not exist
                        if($city['key'] == $location['key']) {
                        $exist = 1;
                        }
                    }
                    if($exist == 0) {
                        $geo_locations['cities'][] = ['key' => $location['key']];
                    }

                    break;
            }
        }

        foreach ($geo_locations as $key => $geo_location) {
            if(count($geo_locations[$key]) == 0) {
                unset($geo_locations[$key]);
            }
        }

        return $geo_locations;
    }

    public function updateAdSet(
        int $adset_id,
        string $name,
        string $billing_event,
        string $bid_strategy,
        int $bid_amount,
        string $budget_type,
        int $budget_amount,
        string $start_time,
        string $end_time,
        array $targeting,
        string $status = null,
        string $objective = null,
        string $pixel_id = null,
        string $custom_event_type = null

    )
    {
        $targeting = $this->generateTargeting($targeting);

        $adset = new AdSet($adset_id);

        $fields = [];
        $params = [
            'name' => $name,
            'billing_event' => $billing_event,
            'bid_strategy' => $bid_strategy,
            $budget_type => $budget_amount * 100,
            'start_time' => $start_time,
            'targeting' => $targeting,
        ];

        if($status) {
            $params['status'] = $status;
        }

        if($objective) {
            // promoted object
            switch ($objective) {
                case FacebookCampaignObjectiveEnum::CONVERSIONS:
                    if($pixel_id)
                    $params['promoted_object']['pixel_id'] = $pixel_id;
                    
                    if($custom_event_type)
                    $params['promoted_object']['custom_event_type'] = FacebookCustomEventTypeEnum::memberByValue($custom_event_type)->value;
                    
                    break;
                
                default:
                    $params['promoted_object'] = [
                        "page_id" => $this->page_id
                    ];
                    break;
            }
        }

        if($end_time) {
            $params['end_time'] = $end_time;
        }
        if($bid_strategy != AdSetBidStrategyValues::LOWEST_COST_WITHOUT_CAP) {
            $params['bid_amount'] = $bid_amount;
        }

        return $adset->updateSelf($fields, $params)->exportAllData();

    }

    public function deleteAdset($adset_id)
    {
        $adset = new AdSet($adset_id);
        return $adset->deleteSelf()->getContent();
    }

    public function updateAdSetStatus(
        int $adset_id,
        string $status
    )
    {

        $adset = new AdSet($adset_id);

        $fields = [];
        $params = [
            "status" => $status
        ];

        return $adset->updateSelf($fields, $params)->exportAllData();
    }

    public function duplicateAdset(
        string $adset_id,
        string $campaign_id,
        bool $get_adset = false
    )
    {
        $url = "{$this->url}{$adset_id}/copies";

        $api = Http::post($url, [
            'access_token' => $this->user_access_token,
            'campaign_id' => $campaign_id
        ]);

        if(!$api->ok()) {
            return [
                'error' => true,
                'message' => $api->json()['error']['error_user_msg'] ?? $api->json()['error']['message'],
                'response' => $api->json()
            ];
        }
        $adset = $api->json();

        if($get_adset) {
            $adset = $this->getSingleAdSet($api->json()['copied_adset_id']);
        }

        return $adset;
    }


    public function targetingSearchLocation(string $q)
    {
        $api = Http::get($this->url .'search', [
            'location_types' => ['country','region','city'],
            'type' => 'adgeolocation',
            'q' => $q,
            'access_token' => $this->user_access_token,
        ]);
        return $api->json();
    }

    public function targetingSearchCountry(string $q)
    {
        $api = Http::get($this->url .'search', [
            'location_types' => ['country'],
            'type' => 'adgeolocation',
            'q' => $q,
            'access_token' => $this->user_access_token,
        ]);
        return $api->json();
    }

    public function targetingSearchLocale(string $q)
    {
        $api = Http::get($this->url .'search', [
            'type' => 'adlocale',
            'q' => $q,
            'access_token' => $this->user_access_token,
        ]);
        return $api->json();
    }

    public function targetingSearchInterest(string $q)
    {
        $api = Http::get($this->url .'search', [
            'type' => 'adinterest',
            'q' => $q,
            'access_token' => $this->user_access_token,
        ]);
        return $api->json();
    }

    public function targetingSearchBehavior(string $q)
    {
        $api = Http::get($this->url .'search', [
            'type' => 'adTargetingCategory',
            'class' => 'behaviors',
            'access_token' => $this->user_access_token,
        ]);

        $res = $api->json();
        
        if($q) {
            $res['data'] = [];
            foreach ($api->json()['data'] as $item) {
                if(isset($item['name'])) {
                    if(strpos(strtolower($item['name']), strtolower($q)) !== false) {
                        $res['data'][] = $item;
                    }
                }
            }
        }

        return $res;
    }

    public function targetingSearchEducationSchool(string $q)
    {
        $api = Http::get($this->url .'search', [
            'type' => 'adeducationschool',
            'q' => $q,
            'access_token' => $this->user_access_token,
        ]);
        return $api->json();
    }

    public function targetingSearchEducationMajor(string $q)
    {
        $api = Http::get($this->url .'search', [
            'type' => 'adeducationmajor',
            'q' => $q,
            'access_token' => $this->user_access_token,
        ]);
        return $api->json();
    }

    public function targetingSearchWorkEmployer(string $q)
    {
        $api = Http::get($this->url .'search', [
            'type' => 'adworkemployer',
            'q' => $q,
            'access_token' => $this->user_access_token,
        ]);
        return $api->json();
    }

    public function targetingSearchJobTitle(string $q)
    {
        $api = Http::get($this->url .'search', [
            'type' => 'adworkposition',
            'q' => $q,
            'access_token' => $this->user_access_token,
        ]);
        return $api->json();
    }

    public function targetingSearchCategory(string $class, string $q)
    {
        $api = Http::get($this->url .'search', [
            'type' => 'adTargetingCategory',
            'class' => $class,
            'access_token' => $this->user_access_token,
        ]);

        $res = $api->json();

        if($q) {
            $res['data'] = [];
            foreach ($api->json()['data'] as $item) {
                if(isset($item['name'])) {
                    if(strpos(strtolower($item['name']), strtolower($q)) !== false) {
                        $res['data'][] = $item;
                    }
                }
            }
        }

        return $res;
    }



}



