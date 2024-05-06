<?php

namespace App\Models\Services;

use App\Models\CCollection;
use App\Models\Channel;
use App\Models\CollectionAd;
use App\Models\CollectionGroup;
use App\Models\CollectionGroupCreative;
use App\Models\Enums\CampaignInAppStatusEnum;
use App\Models\User;
use App\Models\Enums\CollectionAdStatusEnum;
use App\Models\Enums\FacebookCallToActionEnum;
use App\Models\Enums\FacebookCampaignStatusEnum;
use App\Models\FacebookAdset;
use App\Models\FacebookCampaign;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CollectionAdService extends ModelService
{
    private $collectionAd;

    public function __construct(CollectionAd $collectionAd)
    {
        $this->collectionAd = $collectionAd;
        $this->model = $collectionAd; // required
    }

    public static function create(
        CCollection $collection,
        Channel $channel,
        string $ad_account_id,
        FacebookCampaign $campaign,
        FacebookAdset $adset,
        CollectionGroup $group,
        int $ads_number,
        array $add_images = [],
        array $add_title = [],
        array $add_headline = [],
        array $add_text = [],
        array $add_call_to_action = [],
        array $add_url = [],
        CollectionAdStatusEnum $status
    ) {
        if(count($add_images) < $ads_number) {
            return [
                'error' => true,
                'message' => 'Ads number is greater than the group\'s creatives'
            ];
        }

        try {
            DB::beginTransaction();

            $collectionAd = new CollectionAd();

            $collectionAd->user_id = Auth::user()->id;
            $collectionAd->account_id = auth()->user()->account_id;
            $collectionAd->collection_id = $collection->id;
            $collectionAd->channel_id = $channel->id;
            $collectionAd->ad_account_id = $ad_account_id;
            $collectionAd->campaign_id = $campaign->id;
            $collectionAd->adset_id = $adset->id;
            $collectionAd->group_id = $group->id;
            $collectionAd->ads_number = $ads_number;
            $collectionAd->add_images = $add_images;
            $collectionAd->add_title = $add_title;
            $collectionAd->add_headline = $add_headline;
            $collectionAd->add_text = $add_text;
            $collectionAd->add_call_to_action = $add_call_to_action;
            $collectionAd->add_url = $add_url;
            $collectionAd->status = $status;
            $collectionAd->save();

            if($status->is(CollectionAdStatusEnum::PUBLISHED())) {
                CollectionAdService::publishAds($collectionAd);
            }

            DB::commit();
            return $collectionAd;
        } catch (\FacebookAds\Http\Exception\RequestException $th) {
            DB::rollBack();
            return [
                'error' => true,
                'message' => $th->getErrorUserMessage() ?? $th->getMessage()
            ];
        }
    }

    public function update(
        int $channel_id,
        string $ad_account_id,
        int $campaign_id,
        int $adset_id,
        int $ads_number,
        array $add_images,
        array $add_title,
        array $add_headline,
        array $add_text,
        array $add_call_to_action,
        array $add_url,
        CollectionAdStatusEnum $status
    )
    {
        if($this->collectionAd->status->is(CollectionAdStatusEnum::PUBLISHED())) {
            return [
                'error' => true,
                'message' => 'Collection Ads is already published'
            ];
        }

        try {
            DB::beginTransaction();

            $this->collectionAd->channel_id = $channel_id;
            $this->collectionAd->ad_account_id = $ad_account_id;
            $this->collectionAd->campaign_id = $campaign_id;
            $this->collectionAd->adset_id = $adset_id;
            $this->collectionAd->ads_number = $ads_number;
            $this->collectionAd->add_images = $add_images;
            $this->collectionAd->add_title = $add_title;
            $this->collectionAd->add_headline = $add_headline;
            $this->collectionAd->add_text = $add_text;
            $this->collectionAd->add_call_to_action = $add_call_to_action;
            $this->collectionAd->add_url = $add_url;
            $this->collectionAd->status = $status;
            $this->collectionAd->save();

            if($status->is(CollectionAdStatusEnum::PUBLISHED())) {
                $this->publishAds($this->collectionAd);
            }

            DB::commit();
            return $this->collectionAd->fresh();
        } catch (\FacebookAds\Http\Exception\RequestException $th) {
            DB::rollBack();
            return [
                'error' => true,
                'message' => $th->getErrorUserMessage() ?? $th->getMessage()
            ];
        }
    }

    public static function preview(
        int $ads_number,
        array $add_images,
        array $add_title,
        array $add_headline,
        array $add_text,
        array $add_call_to_action,
        array $add_url
    )
    {
        return CollectionAdService::publishAds(
            [
                'ads_number' => $ads_number,
                'add_images' => $add_images,
                'add_title' => $add_title,
                'add_headline' => $add_headline,
                'add_text' => $add_text,
                'add_url' => $add_url,
                'add_call_to_action' => $add_call_to_action,
            ],
            true
        );
    }

    public static function publishAds($collectionAd, $preview = false)
    {
        $ads = [];
        while (count($ads) < $collectionAd['ads_number']) {
            
            $title_key = array_rand($collectionAd['add_title']);
            $primary_text_key = array_rand($collectionAd['add_title']);
            $headline_key = array_rand($collectionAd['add_headline']);
            $description_key = array_rand($collectionAd['add_text']);
            $display_link_key = array_rand($collectionAd['add_url']);
            $call_to_action_key = array_rand($collectionAd['add_call_to_action']);

            $publishAd = [
                'title' => [
                    'key' => $title_key,
                    'value' => $collectionAd['add_title'][$title_key]
                ],
                'primary_text' => [
                    'key' => $primary_text_key,
                    'value' => $collectionAd['add_title'][$primary_text_key]
                ],
                'headline' => [
                    'key' => $headline_key,
                    'value' => $collectionAd['add_headline'][$headline_key]
                ],
                'description' => [
                    'key' => $description_key,
                    'value' => $collectionAd['add_text'][$description_key]
                ],
                'display_link' => [
                    'key' => $display_link_key,
                    'value' => $collectionAd['add_url'][$display_link_key]
                ],
                'call_to_action' => [
                    'key' => $call_to_action_key,
                    'value' => FacebookCallToActionEnum::memberByValue($collectionAd['add_call_to_action'][$call_to_action_key])
                ],
            ];

            $repeat = false;
            if(count($ads) > 0) {
                foreach($ads as $ad) {
                    $counter = 0;
                    foreach($ad as $key => $content) {
                        if($key != 'image' && $content['key'] == $publishAd[$key]['key']) {
                            $counter++;
                        }
                    }
                    $repeat = $counter == 6 ? true : $repeat;
                }
            }

            if(!$repeat) {
                $image_key = array_rand($collectionAd['add_images']);

                if(!$preview) {
                    $info = pathinfo($collectionAd['add_images'][$image_key]);
                    $contents = file_get_contents($collectionAd['add_images'][$image_key]);
                    $file = '/tmp/' . $info['basename'];
                    file_put_contents($file, $contents);
                    $publishAd['image'] = new UploadedFile($file, $info['basename']);
    
                    $facebookAd = FacebookAdModelService::create(
                        FacebookAdset::find($collectionAd['adset_id']),
                        null,
                        CampaignInAppStatusEnum::PUBLISH(),
                        null,
                        $publishAd['title']['value'],
                        $publishAd['primary_text']['value'],
                        $publishAd['headline']['value'],
                        $publishAd['description']['value'],
                        $publishAd['display_link']['value'],
                        $publishAd['call_to_action']['value'],
                        $publishAd['image']
                    );
    
                    $collectionAd->facebookAds()->attach($facebookAd->id);
                } else {
                    $publishAd['image'] = $collectionAd['add_images'][$image_key];
                }

                $ads[] = $publishAd;
            }
        }

        return $ads;
    }

    public static function duplicate(
        int $collection_id,
        int $channel_id,
        string $ad_account_id,
        int $campaign_id,
        int $adset_id,
        int $group_id,
        int $ads_number,
        array $add_images,
        array $add_title,
        array $add_headline,
        array $add_text,
        array $add_call_to_action,
        array $add_url
    )
    {
        $collectionAd = new CollectionAd();

        $collectionAd->user_id = Auth::user()->id;
        $collectionAd->account_id = auth()->user()->account_id;
        $collectionAd->collection_id = $collection_id;
        $collectionAd->channel_id = $channel_id;
        $collectionAd->ad_account_id = $ad_account_id;
        $collectionAd->campaign_id = $campaign_id;
        $collectionAd->adset_id = $adset_id;
        $collectionAd->group_id = $group_id;
        $collectionAd->ads_number = $ads_number;
        $collectionAd->add_images = $add_images;
        $collectionAd->add_title = $add_title;
        $collectionAd->add_headline = $add_headline;
        $collectionAd->add_text = $add_text;
        $collectionAd->add_call_to_action = $add_call_to_action;
        $collectionAd->add_url = $add_url;
        $collectionAd->status = CollectionAdStatusEnum::DRAFT;
        $collectionAd->save();

        return $collectionAd;
    }

    public function delete(): bool
    {
        $this->collectionAd->delete();
        return true;
    }

    public function syncFacebookAd(array $fb_ad_ids = [])
    {
        if (empty($fb_ad_ids)) {
            return $this->collectionAd->facebookAds()->detach();
        }

        return $this->collectionAd->facebookAds()->sync($fb_ad_ids);
    }
}
