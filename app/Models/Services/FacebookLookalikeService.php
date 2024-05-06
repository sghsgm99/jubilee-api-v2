<?php

namespace App\Models\Services;

use App\Services\FacebookService;
use App\Models\Channel;
use App\Models\Enums\FacebookAudienceTypeEnum;
use App\Models\Enums\FacebookPageEventFilterValueEnum;
use App\Models\FacebookAudience;
use App\Services\FacebookAudienceService;
use App\Services\FacebookChildBMService;

class FacebookLookalikeService extends FacebookService
{
    protected $base_url;

    public function __construct(FacebookAudience $facebook_audience)
    {
        $this->base_url = 'https://graph.facebook.com/' . config('facebook.version') . '/';
        $this->facebook_audience = $facebook_audience;
        $this->model = $facebook_audience; // required
    }

    public static function createFacebookCustomAudience(
        Channel $channel,
        string $audience_name,
        string $audience_description = null,
        FacebookAudienceTypeEnum $audience_type,
        string $event_source_id,
        int $retention_days,
        FacebookPageEventFilterValueEnum $event_filter_value,
        string $audience_id = null,
        string $ad_account = null
    )
    {
        $audience = null;

        // generate access token
        $child_bm = ChannelFacebookService::createAccessToken($channel->channelFacebook->child_business_manager_id);
        if(!isset($child_bm['error'])) {
            $channel->channelFacebook->access_token = $child_bm['access_token'];
            $channel->channelFacebook->update();
        }

        if(!$audience_id) {
            $audience =  FacebookAudienceService::resolve($channel)->createCustomAudience(
                $channel,
                $audience_name,
                $audience_description,
                $audience_type,
                $event_source_id,
                $retention_days,
                $event_filter_value,
                $ad_account
            );
            $audience_id = $audience['id'] ?? null;
        } else {
            $audience =  FacebookAudienceService::resolve($channel)->updateCustomAudience(
                $audience_id,
                $channel,
                $audience_name,
                $audience_description,
                $audience_type,
                $event_source_id,
                $retention_days,
                $event_filter_value
            );

            $audience = $audience->json();

        }

        if($audience && !isset($audience['error'])) {
            $facebook_audience = new FacebookAudience;
            $facebook_audience->audience_name = $audience_name;
            $facebook_audience->audience_description = $audience_description;
            $facebook_audience->audience_id = $audience_id;
            $facebook_audience->audience_type = $audience_type;
            $facebook_audience->setup_details = [
                "retention_days" => $retention_days,
                "event_source_id" => $event_source_id,
                "event_filter_value" => $event_filter_value->key
            ];
            $facebook_audience->account_id = $channel->account_id;
            $facebook_audience->channel()->associate($channel);

            $facebook_audience->save();

            return $facebook_audience;
        }

        if(isset($audience['error'])) {
            $ErrorMessage = $audience['error']['error_user_msg'] ?? $audience['error']['message'];
        }else {
            $ErrorMessage = 'Failed to create Custom Audience'; 
        }

        return ['error' => true, 'message' => $ErrorMessage];

    }

    public function updateFacebookCustomeAudience(
        Channel $channel,
        string $audience_name,
        string $audience_description = null,
        FacebookAudienceTypeEnum $audience_type,
        string $event_source_id,
        int $retention_days,
        FacebookPageEventFilterValueEnum $event_filter_value
    )
    {
        $audience =  FacebookAudienceService::resolve($channel)->updateCustomAudience(
            $this->facebook_audience->audience_id,
            $channel,
            $audience_name,
            $audience_description,
            $audience_type,
            $event_source_id,
            $retention_days,
            $event_filter_value
        );

        if($audience->ok()) {
            $this->facebook_audience->audience_name = $audience_name;
            $this->facebook_audience->audience_description = $audience_description;
            $this->facebook_audience->audience_type = $audience_type;
            $this->facebook_audience->setup_details = [
                "retention_days" => $retention_days,
                "event_source_id" => $event_source_id,
                "event_filter_value" => $event_filter_value->key
            ];

            $this->facebook_audience->save();
            return $this->facebook_audience->fresh();
        }

        $ErrorMessage = $audience['error']['message'] ?? 'Failed to update Custom Audience'; 
        return ['error' => true, 'message' => $ErrorMessage];

    }

    public static function deleteFacebookAudience(
        Channel $channel, 
        string $audience_id
    )
    {
        $audience =  FacebookAudienceService::resolve($channel)->deleteAudience($audience_id, $channel->channelFacebook->access_token);

        if($audience->ok()) {
            $facebook_audience = FacebookAudience::where('audience_id', $audience_id)->first();
            if($facebook_audience) {
                $facebook_audience->delete();
            }
            return true;
        }

        $ErrorMessage = $audience['error']['message'] ?? 'Failed to update Custom Audience'; 
        return ['error' => true, 'message' => $ErrorMessage];
    }

    public static function getFacebookAudience(
        Channel $channel,
        string $custom = null,
        string $search = null,
        string $type = null,
        string $source = null,
        string $ad_account = null
    )
    {
        // if($custom) {
        //     return $channel->facebook_custom_audiences;
        // }

        return FacebookAudienceService::resolve($channel)->getAudience(
            $custom,
            $search,
            $type,
            $source,
            $ad_account
        );
    }

    public function getSingleFacebookAudience()
    {
        $setup_details = $this->facebook_audience->setup_details;
        if($this->facebook_audience->audience_type == FacebookAudienceTypeEnum::LOOKALIKE) {
            $setup_details['facebook_audience'] = FacebookAudience::find($this->facebook_audience->setup_details['facebook_audience_id']); 
        } else {
            $setup_details['event_filter_value'] = str_replace(' ', '_', $this->facebook_audience->setup_details['event_filter_value']);
        }

        $this->facebook_audience->setup_details = $setup_details;
        
        return $this->facebook_audience;
    }

    public static function getAudienceFromPlatform(Channel $channel, $facebook_audience_id)
    {
        $audience = FacebookAudienceService::resolve($channel)->getAudienceFromPlatform($channel, $facebook_audience_id);

        return $audience;
    }

    public static function getFacebookPages(Channel $channel)
    {
        return FacebookChildBMService::resolve($channel)->getPagesFromChild($channel->channelFacebook, false);
    }


    public static function createFacebookLookalikeAudience(
        Channel $channel,
        $facebook_audience_id,
        string $audience_name,
        string $audience_description,
        FacebookAudienceTypeEnum $audience_type,
        float $starting_size,
        float $ending_size,
        string $country,
        string $audience_id = null,
        string $ad_account = null
    )
    {
        $audience = null;

        if(!$audience_id) {
            $audience =  FacebookAudienceService::resolve($channel)->createLookalikeAudience(
                $facebook_audience_id,
                $audience_name,
                $audience_description,
                $starting_size,
                $ending_size,
                $country,
                $ad_account
            );
            $audience_id = $audience['id'];
        } else {
            $audience =  FacebookAudienceService::resolve($channel)->updateLookalikeAudience(
                $audience_id,
                $audience_name,
                $audience_description
            );
            $audience = $audience->json();
        }

        if($audience && !isset($audience['error'])) {
            $new_facebook_audience = new FacebookAudience;
            $new_facebook_audience->audience_name = $audience_name;
            $new_facebook_audience->audience_description = $audience_description == '' ? null : $audience_description;
            $new_facebook_audience->audience_id = $audience['id'];
            $new_facebook_audience->audience_type = $audience_type;
            $new_facebook_audience->setup_details = [
                "facebook_audience_id" => $facebook_audience_id,
                "starting_size" => $starting_size,
                "ending_size" => $ending_size,
                "country" => $country,
            ];
            $new_facebook_audience->account_id = $channel->account_id;
            $new_facebook_audience->channel()->associate($channel);

            $new_facebook_audience->save();

            return $new_facebook_audience;
        }

        if(isset($audience['error'])) {
            $ErrorMessage = $audience['error']['error_user_msg'] ?? $audience['error']['message'];
        }else {
            $ErrorMessage = 'Failed to create Custom Audience'; 
        }
        return ['error' => true, 'message' => $ErrorMessage];
    }

    public function updateFacebookLookalikeAudience(
        Channel $channel,
        string $audience_name,
        string $audience_description
    )
    {
        $audience =  FacebookAudienceService::resolve($channel)->updateLookalikeAudience(
            $this->facebook_audience,
            $audience_name,
            $audience_description
        );
        
        if($audience->ok()) {
            $this->facebook_audience->audience_name = $audience_name;
            $this->facebook_audience->audience_description = $audience_description == '' ? null : $audience_description;

            $this->facebook_audience->save();
            return $this->facebook_audience;
        }

        $ErrorMessage = $audience['error']['message'] ?? 'Failed to create Custom Audience'; 
        return ['error' => true, 'message' => $ErrorMessage];
    }

}
