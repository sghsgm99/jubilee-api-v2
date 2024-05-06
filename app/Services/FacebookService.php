<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\ChannelFacebook;
use App\Models\Enums\ChannelFacebookTypeEnum;
use App\Models\Enums\FacebookBusinessManagerTypeEnum;
use App\Models\FacebookAdAccount;
use FacebookAds\Api;
use FacebookAds\Logger\CurlLogger;
use FacebookAds\Object\Ad;
use FacebookAds\Object\AdAccount;
use FacebookAds\Object\AdSet;
use FacebookAds\Object\Campaign;
use FacebookAds\Object\Fields\AdsInsightsFields;
use FacebookAds\Object\Values\AdsInsightsDatePresetValues;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class FacebookService
{
    const BASE_URL = 'https://graph.facebook.com/';

    protected $url = '';
    protected $app_id = '';
    protected $app_secret = '';
    protected $parent_business_manager_id = '';
    protected $loc = '';
    protected $pimary_page_id = '';
    protected $parent_access_token = '';

    protected $user_access_token = '';
    protected $app_access_token = '';
    protected $page_access_token = '';
    protected $ad_account_id = '';
    protected string $act_ad_account_id = '';
    protected $page_id = '';
    protected $app_secret_proof = '';
    protected $init = null;

    public static function resolve(
        Channel $channel,
        $analytics_check = false
    ) {
        /** @var FacebookService $self */
        $self = app(static::class);
        $channel_facebook = $channel->channelFacebook ?? null;
        $account = Auth::user()->account;

        $self->url = self::BASE_URL . config('facebook.version') . '/';

        // from facebook config file
        // $self->app_id = config('facebook.parent_bm.app_id');
        // $self->app_secret = config('facebook.parent_bm.app_secret');

        // from accounts table
        $self->app_id = $account->facebook_app_id;
        $self->app_secret = $account->facebook_app_secret;
        $self->parent_business_manager_id = $account->facebook_business_manager_id;
        $self->parent_access_token = $account->facebook_access_token;
        $self->loc = $account->facebook_line_of_credit_id;
        $self->pimary_page_id = $account->facebook_primary_page_id;

        $self->act_ad_account_id = "act_{$channel_facebook->ad_account}";
        $self->ad_account_id = $channel_facebook->ad_account;
        $self->user_access_token = $channel_facebook->access_token;
        $self->page_id = $channel_facebook->page_id;

        // // if channel is standalone
        if ($channel_facebook && $channel_facebook->type->is(ChannelFacebookTypeEnum::STANDALONE())) {
            $self->act_ad_account_id = 'act_' . $channel_facebook->ad_account;
            $self->ad_account_id = $channel_facebook->ad_account;
            $self->user_access_token = $channel_facebook->access_token;
            $self->parent_access_token = $channel_facebook->access_token;
        }

        // if user is test account
        if (Auth::user()->tester || $channel->id == 141) {
            $self->act_ad_account_id = 'act_' . config('facebook.test_ad_account');
            $self->ad_account_id = config('facebook.test_ad_account');
            $self->user_access_token = config('facebook.test_access_token');
            $self->parent_access_token = config('facebook.test_access_token');
        }

        if (!$channel_facebook) {
            abort(400, 'Facebook Channel cannot be resolved. Missing data.');
        }

        if (!$channel_facebook->access_token && $analytics_check) {
            return $self;
        }

        if (!$channel_facebook->access_token && !$analytics_check) {
            abort(400, 'Facebook Channel cannot be resolved. Missing data.');
        }

        $self->app_secret_proof = hash_hmac('sha256', $self->parent_access_token, $self->app_secret);
        $self->init = Api::init($self->app_id, $self->app_secret, $self->parent_access_token);

        if($channel_facebook->child_business_manager_id != $self->parent_business_manager_id && $channel_facebook->access_token && !Auth::user()->tester || $channel_facebook->type->is(ChannelFacebookTypeEnum::STANDALONE())) {
            $self->app_secret_proof = hash_hmac('sha256', $self->user_access_token, $self->app_secret);
            $self->init = Api::init($self->app_id, $self->app_secret, $self->user_access_token);
        }

        $self->init->setLogger(new CurlLogger());
        
        if (!$self->init && !$analytics_check) {
            abort('400', 'Facebook Channel cannot be resolved. Missing data.');
        }

        return $self;
    }

    public function generateAppAccessTokent()
    {
        $res = Http::get("https://graph.facebook.com/oauth/access_token?client_id={$this->app_id}&client_secret={$this->app_secret}&grant_type=client_credentials");

        if($res->json()['access_token']) {
            return $res->json()['access_token'];
        }

        return null;
    }

    public function facebookInsights(
        string $step,
        string $id,
        string $rule
    )
    {
        if (!$this->init) {
            return [];
        }
        try {

            $act = null;

            switch ($step) {
                case 'campaign':
                    $act = new Campaign($id);
                    break;
                case 'adset':
                    $act = new AdSet($id);
                    break;
                case 'ad':
                    $act = new Ad($id);
                    break;

            }

            if(!$act) {
                return [];
            }

            $fields = [
                AdsInsightsFields::ACCOUNT_NAME,
                AdsInsightsFields::OBJECTIVE,
                AdsInsightsFields::CLICKS,
                AdsInsightsFields::CONVERSIONS,
                AdsInsightsFields::IMPRESSIONS,
                AdsInsightsFields::REACH,
                AdsInsightsFields::SPEND,
                AdsInsightsFields::CPC,
                AdsInsightsFields::CPM,
                AdsInsightsFields::CPP,
                AdsInsightsFields::CTR,
                AdsInsightsFields::PURCHASE_ROAS,
                AdsInsightsFields::WEBSITE_PURCHASE_ROAS,
                AdsInsightsFields::MOBILE_APP_PURCHASE_ROAS,
                AdsInsightsFields::DATE_START,
                AdsInsightsFields::DATE_STOP,
            ];

            $params = [
                'date_preset' => AdsInsightsDatePresetValues::MAXIMUM
            ];

            $response = $act->getInsights($fields, $params)->getResponse()->getContent();

            switch ($rule) {
                case 'visitor':
                    return $response['data'][0]['reach'];
                    break;
                case 'spend':
                    return $response['data'][0]['spend'];
                    break;
                case 'revenue':
                    $response['data'][0]['revenue'] = $response['data'][0]['spend'] * ($response['data'][0]['purchase_roas'][0]['value'] ?? 0);
                    return $response['data'][0]['revenue'];
                    break;
            }

        } catch (\FacebookAds\Http\Exception\RequestException $th) {
            return [
                'error' => true,
                'message' => $th->getMessage()
            ];
        }
    }

    public function facebookDetailedInsights()
    {
        if (!$this->init) {
            return [];
        }
        $details = [];
        $act = new AdAccount($this->act_ad_account_id);

        $fields = [
            AdsInsightsFields::CAMPAIGN_ID,
            AdsInsightsFields::CAMPAIGN_NAME,
            AdsInsightsFields::CLICKS,
            AdsInsightsFields::SPEND,
            AdsInsightsFields::REACH,
            AdsInsightsFields::IMPRESSIONS,
            AdsInsightsFields::CPC,
            AdsInsightsFields::CPM,
            AdsInsightsFields::CPP,
            AdsInsightsFields::CTR,
            AdsInsightsFields::ACCOUNT_ID,
            AdsInsightsFields::PURCHASE_ROAS,
            AdsInsightsFields::WEBSITE_PURCHASE_ROAS,
            AdsInsightsFields::MOBILE_APP_PURCHASE_ROAS,
            AdsInsightsFields::DATE_START,
            AdsInsightsFields::DATE_STOP,
        ];
        $params = [
            'date_preset' => AdsInsightsDatePresetValues::MAXIMUM
        ];

        // get campaign insights from channel
        $camps = $act->getCampaigns();

        foreach ($camps as $camp) {
            $details[] = $camp->getInsights($fields, $params)->getResponse()->getContent()['data'];
        }

        return $details;
    }


    public function getFacebookInsight(ChannelFacebook $channelFacebook, $type)
    {
        try {
            $act = new AdAccount($this->act_ad_account_id);
            
            $fields = [
                AdsInsightsFields::ACCOUNT_NAME,
                AdsInsightsFields::CAMPAIGN_NAME,
                AdsInsightsFields::ADSET_NAME,
                AdsInsightsFields::AD_NAME,
                AdsInsightsFields::OBJECTIVE,
                AdsInsightsFields::CLICKS,
                AdsInsightsFields::CONVERSIONS,
                AdsInsightsFields::IMPRESSIONS,
                AdsInsightsFields::REACH,
                AdsInsightsFields::SPEND,
                AdsInsightsFields::CPC,
                AdsInsightsFields::CPM,
                AdsInsightsFields::CPP,
                AdsInsightsFields::CTR,
                AdsInsightsFields::PURCHASE_ROAS,
                AdsInsightsFields::WEBSITE_PURCHASE_ROAS,
                AdsInsightsFields::MOBILE_APP_PURCHASE_ROAS,
                AdsInsightsFields::DATE_START,
                AdsInsightsFields::DATE_STOP,
            ];

            $params = [
                'date_preset' => AdsInsightsDatePresetValues::MAXIMUM,
            ];

            $data = [];
            switch ($type) {
                case 'ad-accounts':
                    $params['level'] = 'account';
                    $params['filtering'] = json_decode('[{"field":"ad.effective_status","operator":"IN","value":["ACTIVE","PAUSED"]}]');
                    if ($response = $act->getInsights($fields, $params)->getResponse()->getContent()['data']) {
                        $response[0]['revenue'] = $response[0]['spend'] * ($response[0]['purchase_roas'][0]['value'] ?? 0);
                        $data[] = $response[0];
                    }
                    break;

                case 'campaigns':
                    $params['level'] = 'campaign';
                    $params['filtering'] = json_decode('[{"field":"ad.effective_status","operator":"IN","value":["ACTIVE","PAUSED"]}]');
                    foreach ($act->getCampaigns() as $campaign) {
                        if ($responses = $campaign->getInsights($fields, $params)->getResponse()->getContent()['data']) {
                            foreach ($responses as $response) {
                                $response['revenue'] = $response['spend'] * ($response['purchase_roas'][0]['value'] ?? 0);
                                $data[] = $response;
                            }
                        }
                    }
                    break;

                case 'ad-sets':
                    $params['level'] = 'adset';
                    $params['filtering'] = json_decode('[{"field":"ad.effective_status","operator":"IN","value":["ACTIVE","PAUSED"]}]');
                    if ($responses = $act->getInsights($fields, $params)->getResponse()->getContent()['data']) {
                        foreach ($responses as $response) {
                            $response['revenue'] = $response['spend'] * ($response['purchase_roas'][0]['value'] ?? 0);
                            $data[] = $response;
                        }
                    }
                    break;

                case 'ads':
                    $params['level'] = 'ad';
                    $params['filtering'] = json_decode('[{"field":"ad.effective_status","operator":"IN","value":["ACTIVE","PAUSED"]}]');
                    if ($responses = $act->getInsights($fields, $params)->getResponse()->getContent()['data']) {
                        foreach ($responses as $response) {
                            $response['revenue'] = $response['spend'] * ($response['purchase_roas'][0]['value'] ?? 0);
                            $data[] = $response;
                        }
                    }
                    break;

                case 'channel':
                    $params['level'] = 'account';
                    $params['filtering'] = json_decode('[{"field":"ad.effective_status","operator":"IN","value":["ACTIVE","PAUSED"]}]');
                    if ($response = $act->getInsights($fields, $params)->getResponse()->getContent()['data']) {
                        $response[0]['revenue'] = $response[0]['spend'] * ($response[0]['purchase_roas'][0]['value'] ?? 0);
                        $data[] = $response[0];
                    }
                    break;

                default:
                    $act = null;
                    break;
            }
            return $data;
        } catch (\FacebookAds\Http\Exception\RequestException $th) {
            return [
                'error' => true,
                'messagge' => $th->getErrorUserMessage() ?? $th->getMessage()
            ];
        }
    }

    public static function getAdLibrary()
    {
        $url = self::BASE_URL . config('facebook.version') . '/ads_archive';

        $payload = [
            "search_terms" => "manila",
            "ad_type" => "POLITICAL_AND_ISSUE_ADS",
            "ad_reached_countries" => "['PH']",
            // "access_token" => config('facebook.parent_bm.access_token'),
            "access_token" => 'EAAGh0whFzxEBALSwLNBYUvqJ4Oz4VRHcDKboCXFAzLZCT1izdpBd4lSbZBq8ieiNRaZCW3ZB2YXrfZAaHl8jlOAAvYRcTO5UyZCoHelYMZA5wLHOcHstsjQtZAkeoZBxqZCHCCcld7ANF71d6ZCD9MFSZA8RVVpvfAYZAFkpd6r0ZBPOt9oRFozhR5ZC6dNJCHkTSUa5zOqCitgaGZAX5xI0VoWtAynhb1RRrN2hl0qMA0ekGDGr8ROQiKBa8msd',
        ];

        $res = Http::get($url, $payload);

        return $res->json();
    }

    public static function updateAdAccounts($business_manager_id)
    {
        $account = Auth::user()->account;
        $business_manager_type = $business_manager_id ? FacebookBusinessManagerTypeEnum::CHILD_BM() : FacebookBusinessManagerTypeEnum::PARENT_BM();
        $business_manager_id = $business_manager_id ?? $account->facebook_business_manager_id;

        $url = self::BASE_URL . config('facebook.version') . '/' . $business_manager_id . '/owned_ad_accounts';

        $payload = [
            'fields' => 'name,id,account_id,permitted_tasks',
            'access_token' => $account->facebook_access_token
        ];
        $api = Http::get( $url,$payload);
        $apiResponse = $api->json();

        if(!$api->ok()) {
            return [
                'error' => true,
                'message' => $apiResponse['error']['error_user_msg'] ?? $apiResponse['error']['message']
            ];
        }

        $next = true;

        while ($next == true) {

            foreach ($apiResponse['data'] as $res) {

                FacebookAdAccount::updateOrCreate(
                    [
                        'ad_account_id' => $res['account_id']
                    ],
                    [
                        'name' => $res['name'],
                        'ad_account_id' => $res['account_id'],
                        'act_ad_account_id' => $res['id'],
                        'business_manager_id' => $business_manager_id,
                        'business_manager_type' => $business_manager_type,
                        'account_id' => Auth::user()->account_id,
                    ]
                );
            }

            $next = false;

            if(isset($apiResponse['paging']['next'])) {
                $apiResponse = Http::get($apiResponse['paging']['next'])->json();
                $next = true;
            }

        }


        return ['response' => 'success'];
    }
}
