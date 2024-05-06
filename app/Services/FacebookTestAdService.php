<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Enums\FacebookCallToActionEnum;
use App\Models\Enums\FacebookCampaignStatusEnum;
use App\Models\Site;
use App\Models\Campaign as CampaignModel;
use App\Models\ChannelFacebook;
use FacebookAds\Object\AdAccount;
use FacebookAds\Object\AdImage;
use FacebookAds\Object\Fields\AdCreativeLinkDataCallToActionValueFields;
use FacebookAds\Object\Fields\AdImageFields;
use FacebookAds\Object\AdCreative;
use FacebookAds\Object\Ad;
use FacebookAds\Object\Fields\AdCreativeFields;
use FacebookAds\Object\Fields\AdFields;
use FacebookAds\Object\AdCreativeLinkData;
use FacebookAds\Object\Fields\AdCreativeLinkDataFields;
use FacebookAds\Object\AdCreativeObjectStorySpec;
use FacebookAds\Object\Fields\AdCreativeObjectStorySpecFields;
use FacebookAds\Object\Campaign;
use FacebookAds\Object\AdSet;
use FacebookAds\Object\Values\PageCallToActionWebDestinationTypeValues;
use Illuminate\Support\Facades\Http;

class FacebookTestAdService extends FacebookService
{
    protected $fields = [
        AdFields::ID,
        AdFields::NAME,
        AdFields::CAMPAIGN_ID,
        AdFields::ADSET_ID,
        AdFields::CREATIVE,
        AdFields::PREVIEW_SHAREABLE_LINK,
        AdFields::CREATED_TIME,
        AdFields::STATUS,
        AdFields::EFFECTIVE_STATUS,
    ];

    public function getAds(
        array $status,
        string $campaign_id,
        string $adset_id
    )
    {

        $fb_entity = new AdAccount($this->act_ad_account_id);
        if ($adset_id) {
            $fb_entity = new Adset($adset_id);
        } else if ($campaign_id) {
            $fb_entity = new Campaign($campaign_id);
        }

        $params = [AdFields::EFFECTIVE_STATUS => $status];

        $resp = $fb_entity->getAds(
            $this->fields,
            $params
        )->getResponse();

        return $resp->getContent();
    }

    public function createAd(
        string $adset_id,
        Article $article,
        Site $site,
        int $page_key,
        string $ad_account
    )
    {
        $ad_account = $ad_account ? 'act_'.$ad_account : $this->act_ad_account_id;

        // First, upload the ad image that you will use in your ad creative
        $ad_image = new AdImage(null, $ad_account);

        $img_obj = $article->images->where('is_featured', true)->first() ?? $article->images->first();
        $local_image_path = $img_obj->getLocalFilePath();
        $ad_image->{AdImageFields::FILENAME} = $local_image_path;

        $ad_image->create();

        $link_data = new AdCreativeLinkData();
        $link_data->setData(array(
            AdCreativeLinkDataFields::MESSAGE => $article->title,
            AdCreativeLinkDataFields::LINK => $site->url.'/article/'.$article->slug, // @TODO: replace this link with real one
            AdCreativeLinkDataFields::IMAGE_HASH => $ad_image->{AdImageFields::HASH},
        ));

        $object_story_spec = new AdCreativeObjectStorySpec();
        $object_story_spec->setData(array(
            // AdCreativeObjectStorySpecFields::PAGE_ID => "107476875060963", // @TODO: replace this page_id with real one
            AdCreativeObjectStorySpecFields::PAGE_ID => $page_key, // @TODO: replace this page_id with real one
            AdCreativeObjectStorySpecFields::LINK_DATA => $link_data,
        ));

        $creative = new AdCreative(null, $ad_account);
        $creative->setData(array(
            AdCreativeFields::NAME => $article->title,
            AdCreativeFields::OBJECT_STORY_SPEC => $object_story_spec,
        ));

        $creative->create();

        // Finally, create your ad along with ad creative.
        // Please note that the ad creative is not created independently, rather its
        // data structure is appended to the ad group
        $ad = new Ad(null, $ad_account);
        $ad->setData(array(
            AdFields::NAME => $article->title,
            AdFields::ADSET_ID => $adset_id,
            AdFields::CREATIVE =>array(
                'creative_id' => $creative->id,
            ),
        ));

        $ad->create(array(
            Ad::STATUS_PARAM_NAME => FacebookCampaignStatusEnum::PAUSED,
        ));

        return $ad->getData();
    }

    public function createStandaloneAd(
        string $adset_id,
        int $page_key,
        Site $site,
        CampaignModel $campaign,
        string $ad_account
    ): array
    {
        $ad_account = $ad_account ? 'act_'.$ad_account : $this->act_ad_account_id;

        // First, upload the ad image that you will use in your ad creative
        $image = $campaign->featureImage ?? $campaign->image;

        
        $ad_image = new AdImage(null, $ad_account);
        $ad_image->{AdImageFields::FILENAME} = $image->getLocalFilePath();
        $ad_image->create();

        $link_data = new AdCreativeLinkData();
        $link_data->setData(array(
            AdCreativeLinkDataFields::CALL_TO_ACTION => array(
                'type' => $campaign->call_to_action->value, // AdCreativeCallToActionTypeValues::LEARN_MORE,
                'value' => array(
                    AdCreativeLinkDataCallToActionValueFields::LINK => ($campaign->display_link) ?: $site->url,
                ),
            ),
            AdCreativeLinkDataFields::CAPTION => $campaign->primary_text,
            AdCreativeLinkDataFields::MESSAGE => $campaign->headline,
            AdCreativeLinkDataFields::LINK => ($campaign->display_link) ?: $site->url,
            AdCreativeLinkDataFields::CAPTION => ($campaign->display_link) ?: $site->url,
            AdCreativeLinkDataFields::DESCRIPTION => $campaign->ad_description,
            AdCreativeLinkDataFields::IMAGE_HASH => $ad_image->{AdImageFields::HASH},
        ));

        $object_story_spec = new AdCreativeObjectStorySpec();
        $object_story_spec->setData(array(
            AdCreativeObjectStorySpecFields::PAGE_ID => $page_key,
            AdCreativeObjectStorySpecFields::LINK_DATA => $link_data
        ));

        $creative = new AdCreative(null, $ad_account);
        $creative->setData(array(
            AdCreativeFields::NAME => $campaign->headline,
            AdCreativeFields::OBJECT_STORY_SPEC => $object_story_spec,
        ));

        // dd($creative);

        $creative->create();

        // Finally, create your ad along with ad creative.
        // Please note that the ad creative is not created independently, rather its
        // data structure is appended to the ad group
        $ad = new Ad(null, $ad_account);
        $ad->setData(array(
            AdFields::NAME => $campaign->primary_text,
            AdFields::ADSET_ID => $adset_id,
            AdFields::CREATIVE =>array(
                'creative_id' => $creative->id,
            ),
        ));

        $ad->create(array(
            Ad::STATUS_PARAM_NAME => FacebookCampaignStatusEnum::PAUSED,
        ));

        return $ad->getData();
    }

    public function deleteAd(string $ad_id)
    {
        $ad = new Ad($ad_id);
        $ad->deleteSelf();
        return ['success' => true];
    }

    public function getSingleAd(string $ad_id)
    {
        $ad = new Ad($ad_id);
        $params = [];

        $ads = $ad->getSelf(
            $this->fields,
            $params
        )->exportAllData();

        $creative = new AdCreative($ads['creative']['id']);
        $creatives = $creative->getSelf(
            [
                AdCreativeFields::NAME,
                AdCreativeFields::OBJECT_STORY_SPEC
            ],
            []
        )->exportAllData();

        $ads['creative'] = $creatives;
        return $ads;

    }

    public function updateAdStatus(
        int $ad_id,
        string $status
    )
    {
        $ad = new Ad($ad_id);

        $fields = [];
        $params = [
            "status" => $status
        ];

        return $ad->updateSelf($fields, $params)->exportAllData();
    }

    public function generatePreview(
        ChannelFacebook $channel_facebook,
        string $primary_text,
        string $headline,
        FacebookCallToActionEnum $call_to_action,
        string $ad_image,
        string $ad_description,
        string $display_link,
        string $ad_account
    )
    {
        $ad_account = $ad_account ? 'act_'.$ad_account : $this->act_ad_account_id;

        $params = [
          'creative' => [
              'object_story_spec' => [
                    'link_data' => [
                        'call_to_action' => [
                            'type' => $call_to_action->value,
                            'value' => ['link' => $display_link]
                        ],
                        'description' => $ad_description,
                        'link' => $display_link,
                        'message' => $primary_text,
                        'name' => $headline,
                        'picture' => $ad_image
                    ],
                    'page_id' => $channel_facebook->page_id
              ]
            ],
          'ad_format' => 'DESKTOP_FEED_STANDARD',
          'access_token' => config('facebook.parent_bm.access_token')
        ];

        
        $url = self::BASE_URL . config('facebook.version') . "/{$ad_account}/generatepreviews";
        $res = Http::get($url, $params);

        return $res->json()['data'] ? $res->json()['data'][0]['body'] : $res->json();
    }
}
