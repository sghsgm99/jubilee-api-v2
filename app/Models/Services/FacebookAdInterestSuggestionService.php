<?php

namespace App\Models\Services;

use App\Models\Campaign;
use App\Models\Enums\CampaignInAppStatusEnum;
use App\Models\Enums\CampaignStatusEnum;
use App\Models\Enums\ChannelPlatformEnum;
use App\Models\Enums\FacebookCampaignStatusEnum;
use App\Models\FacebookAdset;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FacebookAdInterestSuggestionService extends ModelService
{
    public static function baseUrl()
    {
        return "https://graph.facebook.com/".config('facebook.version')."/";
    }

    public static function targetingSearchInterestSuggestionFacebook(string $data)
    {
        $parent_access_token = Auth::user()->account->facebook_access_token;
        
        $payload = [
            'type' => 'adinterest',
            'q' => $data,
            'access_token' => $parent_access_token,
        ];

        $api = Http::get(self::baseUrl() . 'search', $payload);

        $ints = [
            'data' => []
        ];

        if($api->ok()) {
            // in order to match the response in jubilee app facebook interest tool table
            foreach ($api->json()['data'] as $vals) {
                $ints['data'][] = [
                    'id' => $vals['id'],
                    'name' => $vals['name'],
                    'path' => isset($vals['topic']) ? [$vals['topic']] : [],
                    'audience_size' => $vals['audience_size_upper_bound']
                ];
            }
        }

        return $ints;
        
    }

    public static function createCampaign(array $interests, int $facebook_campaign_id)
    {
        $adsets = [];

        DB::beginTransaction();
        
        foreach ($interests as $interest) {
            $data = [
                "pixel_id" => null,
                "custom_event_type" => null,
                "billing_event" => null,
                "bid_amount" => 0,
                "bid_strategy" => null,
                "budget_type" => null,
                "budget_amount" => 0,
                "start_time" => null,
                "end_time" => null,
                "targeting" => [
                    "custom_audience" => null,
                    "age_max" => 65,
                    "age_min" => 18,
                    "genders" => [],
                    "geo_locations" => [
                    "location_types" => [],
                    "countries" => [],
                    "locations" => []
                    ],
                    "excluded_geo_locations" => [
                    "locations" => []
                    ],
                    "locales" => [],
                    "device_platforms" => [],
                    "publisher_platforms" => [],
                    "facebook_positions" => [],
                    "messenger_positions" => [],
                    "relationship_statuses" => [],
                    "interests" => $interest,
                    "behaviors" => [],
                    "educational_school" => [],
                    "education_statuses" => [],
                    "education_majors" => [],
                    "work_employer" => [],
                    "work_positions" => [],
                    "life_events" => [],
                    "user_device" => [],
                    "income" => [],
                    "industries" => [],
                    "family_statuses" => []
                ]
            ];
    
            $adset = new FacebookAdset;
            $adset->campaign_id = $facebook_campaign_id;
            $adset->title = $interest['name'];
            $adset->data = $data;
            $adset->status = CampaignInAppStatusEnum::DRAFT;
            $adset->fb_status = FacebookCampaignStatusEnum::PAUSED;
            $adset->user_id = Auth::user()->id;
            $adset->account_id = Auth::user()->account->id;
            $adset->save();

            $adsets[] = $adset;
        }

        DB::commit();

        $message = [
            'status' => 'Success',
            'message' => 'Succesfully created campaign',
            'data' => $adsets
        ];

        return $message;
    }
}
