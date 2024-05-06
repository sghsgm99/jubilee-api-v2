<?php

namespace App\Models\Services;

use App\Jobs\ProcessFacebookAdset;
use App\Models\Channel;
use App\Models\Enums\CampaignInAppStatusEnum;
use App\Models\Enums\FacebookCampaignObjectiveEnum;
use App\Models\Enums\FacebookCampaignStatusEnum;
use App\Models\FacebookAdset;
use App\Models\FacebookCampaign;
use App\Services\FacebookAdSetService;
use App\Services\FacebookCampaignService;
use App\Traits\ImageModelServiceTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FacebookAdsetModelService extends ModelService
{
    use ImageModelServiceTrait;

    /**
     * @var Channel
     */
    private $facebookAdset;

    public function __construct(FacebookAdset $facebookAdset)
    {
        $this->facebookAdset = $facebookAdset;
        $this->model = $facebookAdset; // required
    }

    public static function multipleCreate(object $requests)
    {
        $adsets = [];
        foreach ($requests->validated() as $request) {
            $adsets[] = self::create(
                FacebookCampaign::findOrFail($request['campaign_id']),
                $request['title'],
                $request['adset'],
                CampaignInAppStatusEnum::memberByValue($request['status'])
            );
        }

        return $adsets;
    }

    public static function create(
        FacebookCampaign $facebook_campaign,
        string $title,
        array $data,
        CampaignInAppStatusEnum $status
    )
    {
        try {
            DB::beginTransaction();

            $facebookAdset = new FacebookAdset;
            $facebookAdset->campaign_id = $facebook_campaign->id;
            $facebookAdset->title = $title;
            $facebookAdset->data = $data;
            $facebookAdset->status = $status;
            $facebookAdset->fb_status = FacebookCampaignStatusEnum::PAUSED();
            $facebookAdset->user_id = Auth::user()->id;
            $facebookAdset->account_id = Auth::user()->account_id;
            $facebookAdset->save();

            DB::commit();

            if ($facebookAdset->status->isNot(CampaignInAppStatusEnum::DRAFT())) {
                ProcessFacebookAdset::dispatch($facebookAdset);
            }

            return $facebookAdset;
        } catch (\Throwable $th) {
            DB::rollBack();
            return [
                'error' => true,
                'message' => $th->getErrorUserMessage() ?? $th->getMessage()
            ];
        }
    }

    public function update(
        string $title,
        array $data,
        CampaignInAppStatusEnum $status
    )
    {
        try {
            DB::beginTransaction();

            $this->facebookAdset->title = $title;
            $this->facebookAdset->data = $data;
            $this->facebookAdset->status = $status;
            $this->facebookAdset->save();

            DB::commit();
            $facebookAdset = $this->facebookAdset->fresh();

            if ($this->facebookAdset->status->isNot(CampaignInAppStatusEnum::DRAFT())) {
                ProcessFacebookAdset::dispatch($facebookAdset);
            }

            return $facebookAdset;
        } catch (\Throwable $th) {
            DB::rollBack();
            return ['error' => $th->getErrorUserMessage() ?? $th->getMessage()];
        }
    }

    /**
     * @throws \Exception
     */
    public function publishAdset()
    {
        if ($this->facebookAdset->status->is(CampaignInAppStatusEnum::DRAFT())) {
            return [];
        }

        if (! $this->facebookAdset->campaign->fb_campaign_id) {
            throw new \Exception('The campaign of this adset is not yet published');
        }

        $fbCampaign = $this->facebookAdset->campaign;
        $fbAdsetService = FacebookAdSetService::resolve($fbCampaign->channel);

        // update adset coz it's already publish
        if ($this->facebookAdset->fb_adset_id) {
            try {
                $fbAdsetService->updateAdset(
                    $this->facebookAdset->fb_adset_id,
                    $this->facebookAdset->title,
                    $this->facebookAdset->data['billing_event'],
                    $this->facebookAdset->data['bid_strategy'],
                    $this->facebookAdset->data['bid_amount'] ?? 0,
                    $this->facebookAdset->data['budget_type'],
                    $this->facebookAdset->data['budget_amount'],
                    $this->facebookAdset->data['start_time'],
                    $this->facebookAdset->data['end_time'] ?? '',
                    $this->facebookAdset->data['targeting'],
                    $this->facebookAdset->fb_status->value,
                    $fbCampaign->objective->value,
                    $this->facebookAdset->data['pixel_id'] ?? null,
                    $this->facebookAdset->data['custom_event_type'] ?? null
                );
            } catch (\Throwable $th) {
                throw new \Exception($th->getErrorUserMessage() ?? $th->getMessage());
            }
        }

        // publish adset to facebook
        if (! $this->facebookAdset->fb_adset_id) {
            try {
                $fbAdset = $fbAdsetService->createAdset(
                    $fbCampaign->fb_campaign_id,
                    $this->facebookAdset->title,
                    $this->facebookAdset->data['billing_event'],
                    $this->facebookAdset->data['bid_amount'] ?? 0,
                    $this->facebookAdset->data['bid_strategy'],
                    $this->facebookAdset->data['budget_type'],
                    $this->facebookAdset->data['budget_amount'],
                    $this->facebookAdset->data['start_time'],
                    $this->facebookAdset->data['end_time'] ?? '',
                    $this->facebookAdset->data['targeting'],
                    $fbCampaign->ad_account_id,
                    $fbCampaign->objective->value,
                    $this->facebookAdset->data['pixel_id'] ?? null,
                    $this->facebookAdset->data['custom_event_type'] ?? null
                );
            } catch (\Throwable $th) {
                throw new \Exception($th->getErrorUserMessage() ?? $th->getMessage());
            }
        }

        $this->facebookAdset->fb_adset_id = $fbAdset['id'] ?? $this->facebookAdset->fb_adset_id;
        $this->facebookAdset->save();

        $this->facebookAdset->setProcessToComplete();
        return $this->facebookAdset->fresh();
    }

    public function duplicate(
        ?FacebookCampaign $facebookCampaign,
        $deep = false
    )
    {
        if($this->facebookAdset->fb_adset_id || $this->facebookAdset->status->isNot(CampaignInAppStatusEnum::DRAFT())) {
            $adset = FacebookAdSetService::resolve($this->facebookAdset->campaign->channel)
            ->duplicateAdset(
                $this->facebookAdset->fb_adset_id,
                $facebookCampaign->fb_campaign_id ?? $this->facebookAdset->campaign->fb_campaign_id,
                true
            );

            if(isset($adset['error'])) {
                return $adset;
            }
        }

        $duplicateAdset = $this->facebookAdset->replicate()->fill([
            'fb_adset_id' => $adset['id'] ?? $this->facebookAdset->fb_adset_id,
            'title' => $adset['name'] ?? $this->facebookAdset->title.' - Copy'
        ]);

        if($facebookCampaign) {
            $duplicateAdset->campaign_id = $facebookCampaign->id;
        }

        $duplicateAdset->save();

        if($deep) {
            foreach ($this->facebookAdset->ads as $ad) {
                $ad->Service()->duplicate($duplicateAdset);
            }
        }

        return $duplicateAdset;
    }

    public function toggleStatus()
    {
        if(!$this->facebookAdset->fb_adset_id) {
            return [
                'error' => true,
                'message' => 'Cannot toggle on/off when Facebook Adset is not yet published'
            ];
        }

        try {
            DB::beginTransaction();

            $this->facebookAdset->fb_status = $this->facebookAdset->fb_status->isNot(FacebookCampaignStatusEnum::PAUSED())
                ? FacebookCampaignStatusEnum::PAUSED()
                : FacebookCampaignStatusEnum::ACTIVE();
            $this->facebookAdset->save();

            DB::commit();
            $facebookAdset = $this->facebookAdset->fresh();

            if ($this->facebookAdset->status->isNot(CampaignInAppStatusEnum::DRAFT())) {
                ProcessFacebookAdset::dispatch($facebookAdset);
            }

            return $facebookAdset;
        } catch (\Throwable $th) {
            DB::rollBack();
            return [
                'error' => true,
                'message' => $th->getErrorUserMessage() ?? $th->getMessage()
            ];
        }
    }

    public function delete(): bool
    {
        if($this->facebookAdset->ads->count() > 0) {
            return [
                'error' => true,
                'message' => 'Cannot delete adset if ads are still available'
            ];
        }

        if($this->facebookAdset->fb_adset_id) {
            FacebookCampaignService::resolve($this->facebookAdset->campaign->channel)->deleteCampaign($this->facebookAdset->fb_adset_id);
        }

        return parent::delete();
    }

    public static function createMultipleTest(
        FacebookCampaign $campaign,
        int $copies
    )
    {
        if($campaign->channel_id != 141) {
            return [
                'error' => true,
                'message' => 'Facebook Campaign does not have Test Channel'
            ];
        }

        $adset = [
            "title" => "MKNZ Digital Campaign 1 > Adset 1",
            "status" => 2,
            "campaign_id" => 11,
            "adset" => [
                "billing_event" => "IMPRESSIONS",
                "bid_strategy" => "LOWEST_COST_WITHOUT_CAP",
                "bid_amount" => 1,
                "budget_type" => "lifetime_budget",
                "budget_amount" => 9,
                "start_time" => "2022-07-28T11:33:00.000Z",
                "end_time" => "2022-08-06T11:33:00.000Z",
                "targeting" => [
                    "age_max" => 65,
                    "age_min" => 18,
                    "genders" => [
                        1,
                        2
                    ],
                    "geo_locations" => [
                        "location_types" => [
                            "travel_in"
                        ],
                        "locations" => [
                            [
                                "key" => "US",
                                "name" => "United States",
                                "type" => "country",
                                "country_code" => "US",
                                "country_name" => "United States",
                                "supports_region" => 1,
                                "supports_city" => 1
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $adsets = [];

        for ($i=1; $i <= $copies; $i++) {
            $adset['title'] = "{$campaign->title} > Adset {$i}";
            $adsets[] = self::create(
                $campaign,
                $adset['title'],
                $adset['adset'],
                CampaignInAppStatusEnum::memberByValue($adset['status'])
            );
        }

        return $adsets;
    }
}
