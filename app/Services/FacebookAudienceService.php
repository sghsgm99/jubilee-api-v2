<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\Enums\FacebookAudienceTypeEnum;
use App\Models\Enums\FacebookPageEventFilterValueEnum;
use App\Models\FacebookAudience;
use FacebookAds\Object\AdAccount;
use FacebookAds\Object\CustomAudience;
use FacebookAds\Object\Fields\CustomAudienceFields;
use FacebookAds\Object\Values\CustomAudienceSubtypes;
use Illuminate\Support\Facades\Http;

class FacebookAudienceService extends FacebookService
{

    public function getAudience(
        string $custom = null,
        string $search = null,
        string $type = null,
        string $source = null,
        string $ad_account = null
    )
    {
        $act_ad_account = $ad_account ? "act_{$ad_account}" : $this->act_ad_account_id;

        $ad = new AdAccount($act_ad_account);
        
        $fields = [
            CustomAudienceFields::ID,
            CustomAudienceFields::NAME,
            CustomAudienceFields::DESCRIPTION,
            CustomAudienceFields::RETENTION_DAYS,
            CustomAudienceFields::ACCOUNT_ID,
            CustomAudienceFields::APPROXIMATE_COUNT_LOWER_BOUND,
            CustomAudienceFields::APPROXIMATE_COUNT_UPPER_BOUND,
            CustomAudienceFields::DELIVERY_STATUS,
            CustomAudienceFields::IS_VALUE_BASED,
            CustomAudienceFields::LOOKALIKE_AUDIENCE_IDS,
            CustomAudienceFields::LOOKALIKE_SPEC,
            CustomAudienceFields::PIXEL_ID,
            CustomAudienceFields::RULE,
            CustomAudienceFields::RULE_AGGREGATION,
            CustomAudienceFields::SUBTYPE,
            CustomAudienceFields::TIME_CREATED,
            CustomAudienceFields::TIME_UPDATED,
            CustomAudienceFields::TIME_CONTENT_UPDATED,
        ];

        $custom_audience = $ad->getCustomAudiences($fields)->getResponse()->getContent();

        $audiences = [];

        $next = true;
        $next_custom_audience = null;

        while ($next == true) {
            
            if($next_custom_audience) {
                $custom_audience = $next_custom_audience;
            }
            
            foreach ($custom_audience['data'] as $audience) {
                $audience['audience_type'] = $audience['subtype'] == 'ENGAGEMENT' ? 'PAGE' : $audience['subtype'];
                $audience['find'] = $search || $type || $source ? 0 : 1;

                // search by name
                if($search && strpos(strtolower($audience['name']), strtolower($search)) !== false) {
                    $audience['find'] = 1;
                }

                // search by audience_id
                if($search && strpos($audience['id'], strtolower($search)) !== false) {
                    $audience['find'] = 1;
                }

                // filter by type
                if($type && $type == 'lookalike' && $audience['audience_type'] == 'LOOKALIKE') {
                    $audience['find'] = 1;
                }
                if($type && $type == 'custom' && $audience['audience_type'] != 'LOOKALIKE') {
                    $audience['find'] = 1;
                }
                
                // filter by source
                if($source && $source == $audience['audience_type']) {
                    $audience['find'] = 1;
                }

                // filter only custom audiences
                if($custom && $audience['audience_type'] != 'LOOKALIKE') {
                    $audience['find'] = 1;
                }

                if($audience['find'] == 1 && !in_array($audience['audience_type'], ['LOOKALIKE', 'PAGE'])) {
                    $audience['find'] = 0;
                }

                if($audience['find'] == 1) {
                    $audiences[] = $audience;
                }
                
                        
            }

            $next = false;
            
            if(isset($custom_audience['paging']['next'])) {                
                $next_custom_audience = Http::get($custom_audience['paging']['next'])->json();
                $next = true;
            }

        }

        return $audiences;

    }

    public function getAudienceFromPlatform(Channel $channel, $facebook_audience_id)
    {
        $platform = $this->getAudience(null,$facebook_audience_id);
        $audience = [];

        if(isset($platform[0])) {
            $setup_details = [];
            if($platform[0]['audience_type'] != 'LOOKALIKE') {
                $rules = json_decode($platform[0]['rule'], true);
                $setup_details = [
                    "retention_days" => $platform[0]['retention_days'],
                    "event_source_id" => $rules['inclusions']['rules'][0]['event_sources'][0]['id'],
                    "event_filter_value" => $rules['inclusions']['rules'][0]['filter']['filters'][0]['value']
                ];
            }else {
                $setup_details = [
                    "facebook_audience_id" => $platform[0]['lookalike_spec']['origin'][0]['id'],
                    "starting_size" => $platform[0]['lookalike_spec']['starting_ratio'] ?? 0,
                    "ending_size" => $platform[0]['lookalike_spec']['ratio'],
                    "country" => $platform[0]['lookalike_spec']['country'],
                ];
            }

            $audience = [
                'id' => null,
                'audience_name' => $platform[0]['name'],
                'audience_description' => $platform[0]['description'],
                'audience_id' => $platform[0]['id'],
                'channel_id' => $channel->id,
                'audience_type' => $platform[0]['audience_type'],
            ];

            $audience = array_merge($audience, $setup_details);
        }

        return $audience;
    }

    public function createCustomAudience(
        Channel $channel,
        string $audience_name,
        string $audience_description = null,
        FacebookAudienceTypeEnum $audience_type,
        string $event_source_id,
        int $retention_days,
        FacebookPageEventFilterValueEnum $event_filter_value,
        string $ad_account = null
    )
    {
        $event_value = str_replace(' ', '_', $event_filter_value->key);
        $retention_seconds = $retention_days * 86400;
        
        $fields = [];
        $params = [
            'name' => $audience_name,
            'description' => $audience_description,
            'retention_days' => $retention_days,
            'rule' => [
                'inclusions' => [
                    'operator' => 'or',
                    'rules' => [
                        [
                            'event_sources' => [
                                [
                                    'id' => $event_source_id,
                                    'type' => strtolower($audience_type->value),
                                ]
                            ],
                            'retention_seconds' => $retention_seconds,
                            'filter' => [ 
                                'operator' => 'and',
                                'filters' => [
                                    [
                                        'field' => 'event',
                                        'operator' => 'eq',
                                        'value' => $event_value
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'prefill' => '1'
        ];

        try {

            $act_ad_account = $ad_account ? "act_{$ad_account}" : $this->act_ad_account_id;
            $api = new AdAccount($act_ad_account);
            return $api->createCustomAudience($fields, $params)->exportAllData();

        } catch (\FacebookAds\Http\Exception\RequestException $th) {
            $msg = [];
            $msg['title'] = $th->getErrorUserTitle() ?? '';
            $msg['body'] = $th->getErrorUserMessage() ?? $th->getMessage();

            return [
                'error' => true,
                'message' => $msg
            ];
        }

    }

    public function updateCustomAudience(
        string $audience_id,
        Channel $channel,
        string $audience_name,
        string $audience_description = null,
        FacebookAudienceTypeEnum $audience_type,
        string $event_source_id,
        int $retention_days,
        FacebookPageEventFilterValueEnum $event_filter_value
    )
    {
        $event_value = str_replace(' ', '_', $event_filter_value->key);
        $retention_seconds = $retention_days * 86400;

        $params = [
            'name' => $audience_name,
            'description' => $audience_description,
            'retention_days' => $retention_days,
            'rule' => [
                'inclusions' => [
                    'operator' => 'or',
                    'rules' => [
                        [
                            'event_sources' => [
                                [
                                    'id' => $event_source_id,
                                    'type' => strtolower($audience_type->value),
                                ]
                            ],
                            'retention_seconds' => $retention_seconds,
                            'filter' => [ 
                                'operator' => 'and',
                                'filters' => [
                                    [
                                        'field' => 'event',
                                        'operator' => 'eq',
                                        'value' => $event_value
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'access_token' => $this->user_access_token
        ];
        
        $api = Http::post($this->url . $audience_id, $params);

        return $api;

    }

    public function deleteAudience(string $audience_id)
    {
        $params = ['access_token' => $this->user_access_token];
        $api = Http::delete($this->url . $audience_id, $params);
        return $api;
    }


    public function createLookalikeAudience(
        string $facebook_audience_id,
        string $audience_name,
        string $audience_description = null,
        float $starting_size,
        float $ending_size,
        string $country,
        string $ad_account = null
    )
    {
        $act_ad_account = $ad_account ? "act_{$ad_account}" : $this->act_ad_account_id;

        $lookalike = new CustomAudience(null, $act_ad_account);
        
        $specs = [
            'ratio' => $ending_size,
            'country' => $country
        ];

        if($starting_size > 0) {
            $specs['starting_ratio'] = $starting_size;
        }
        
        $lookalike->setData([
            CustomAudienceFields::NAME => $audience_name,
            CustomAudienceFields::DESCRIPTION => $audience_description,
            CustomAudienceFields::SUBTYPE => CustomAudienceSubtypes::LOOKALIKE,
            CustomAudienceFields::ORIGIN_AUDIENCE_ID => $facebook_audience_id,
            CustomAudienceFields::LOOKALIKE_SPEC => $specs,
        ]);

        return $lookalike->create()->exportAllData();
    }

    public function updateLookalikeAudience(
        FacebookAudience $audience,
        string $audience_name,
        string $audience_description = null
    )
    {
        $params = [
            'name' => $audience_name,
            'description' => $audience_description,
            'access_token' => $this->parent_access_token
        ];
        
        $api = Http::post($this->url . $audience->audience_id, $params);

        return $api;
    }


}



