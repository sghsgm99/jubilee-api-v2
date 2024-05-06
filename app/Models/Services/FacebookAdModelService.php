<?php

namespace App\Models\Services;

use App\Jobs\ProcessFacebookAd;
use App\Models\Article;
use App\Models\Enums\CampaignInAppStatusEnum;
use App\Models\Enums\FacebookCallToActionEnum;
use App\Models\Enums\FacebookCampaignStatusEnum;
use App\Models\FacebookAd;
use App\Models\FacebookAdset;
use App\Models\Site;
use App\Services\FacebookAdService;
use App\Traits\ImageModelServiceTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;

class FacebookAdModelService extends ModelService
{
    use ImageModelServiceTrait;

    /**
     * @var FacebookAd
     */
    private $facebookAd;

    public function __construct(FacebookAd $facebookAd)
    {
        $this->facebookAd = $facebookAd;
        $this->model = $facebookAd; // required
    }

    public static function multipleCreate(object $requests)
    {
        $ads = [];
        foreach ($requests->validated() as $request) {
            if (isset($request['article_id'])) {
                $article = Article::findOrFail($request['article_id']);
            }

            if (isset($request['site_id']) && $request['site_id']) {
                $site = Site::findOrFail($request['site_id']);
            }

            if (isset($request['call_to_action'])) {
                $callToAction = FacebookCallToActionEnum::memberByValue($request['call_to_action']);
            }

            $ads[] = self::create(
                FacebookAdset::findOrFail($request['adset_id']),
                $site ?? null,
                CampaignInAppStatusEnum::memberByValue($request['status']),
                $article ?? null,
                $request['title'] ?? null,
                $request['primary_text'] ?? null,
                $request['headline'] ?? null,
                $request['description'] ?? null,
                $request['display_link'] ?? null,
                $callToAction ?? null,
                $request['image'] ?? null,
            );
        }
        return $ads;
    }

    public static function create(
        FacebookAdset $facebookAdset,
        ?Site $site,
        CampaignInAppStatusEnum $status,
        ?Article $article,
        ?string $title,
        ?string $primary_text,
        ?string $headline,
        ?string $description,
        ?string $display_link,
        ?FacebookCallToActionEnum $callToAction,
        ?UploadedFile $image
    )
    {
        DB::beginTransaction();
        try {
            // store database level
            $facebookAd = new FacebookAd();
            $facebookAd->adset_id = $facebookAdset->id;
            $facebookAd->article_id = $article->id ?? null;
            $facebookAd->site_id = $site->id ?? null;
            $facebookAd->title = $title;
            $facebookAd->primary_text = $primary_text ?? null;
            $facebookAd->headline = $headline ?? null;
            $facebookAd->description = $description ?? null;
            $facebookAd->display_link = $display_link ?? null;
            $facebookAd->call_to_action = $callToAction ?? FacebookCallToActionEnum::LEARN_MORE;
            $facebookAd->status = $status;
            $facebookAd->fb_status = FacebookCampaignStatusEnum::PAUSED;
            $facebookAd->user_id = Auth::id();
            $facebookAd->account_id = Auth::user()->account_id;
            $facebookAd->save();

            // handle file upload
            if ($image) {
                $filename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME) . '_' . time();
                $file = $facebookAd->FileServiceFactory()->uploadFile($image, $filename);

                $image = $facebookAd->Service()->attachImage($image, $file['name']);
                $facebookAd->Service()->markAsFeatured($image->id);
            }

            DB::commit();

            if ($facebookAd->status->isNot(CampaignInAppStatusEnum::DRAFT())) {
                ProcessFacebookAd::dispatch($facebookAd);
            }

            return $facebookAd;
        } catch (\FacebookAds\Http\Exception\RequestException $th) {
            DB::rollBack();
            return [
                'error' => true,
                'message' => $th->getErrorUserMessage() ?? $th->getMessage()
            ];
        }
    }

    public function update(
        ?Site $site,
        CampaignInAppStatusEnum $status,
        ?Article $article,
        ?string $title,
        ?string $primary_text,
        ?string $headline,
        ?string $description,
        ?string $display_link,
        ?FacebookCallToActionEnum $callToAction,
        ?UploadedFile $image
    )
    {
        DB::beginTransaction();
        try {
            $this->facebookAd->article_id = $article->id ?? null;
            $this->facebookAd->site_id = $site->id ?? null;
            $this->facebookAd->title = $title;
            $this->facebookAd->primary_text = $primary_text ?? null;
            $this->facebookAd->headline = $headline ?? null;
            $this->facebookAd->description = $description ?? null;
            $this->facebookAd->display_link = $display_link ?? null;
            $this->facebookAd->call_to_action = $callToAction ?? FacebookCallToActionEnum::LEARN_MORE;
            $this->facebookAd->status = $status;
            $this->facebookAd->save();

            // handle file upload
            if ($image) {
                $filename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME) . '_' . time();
                $file = $this->facebookAd->FileServiceFactory()->uploadFile($image, $filename);

                $image = $this->facebookAd->Service()->attachImage($image, $file['name']);
                $this->facebookAd->Service()->markAsFeatured($image->id);
            }

            DB::commit();
            $facebookAd = $this->facebookAd->fresh();

            if ($this->facebookAd->status->isNot(CampaignInAppStatusEnum::DRAFT())) {
                ProcessFacebookAd::dispatch($facebookAd);
            }

            return $facebookAd;
        } catch (\FacebookAds\Http\Exception\RequestException $th) {
            DB::rollBack();
            return [
                'error' => true,
                'message' => $th->getErrorUserMessage() ?? $th->getMessage()
            ];
        }
    }

    /**
     * @throws \Exception
     */
    public function publishAd()
    {
        if ($this->facebookAd->status->is(CampaignInAppStatusEnum::DRAFT())) {
            return [];
        }

        if (! $this->facebookAd->adset->fb_adset_id) {
            throw new \Exception('The adset of this ad is not yet published');
        }

        $channel = $this->facebookAd->campaign_channel;

        // update ad coz it's already publish
        if ($this->facebookAd->fb_ad_id) {
            try {
                FacebookAdService::resolve($channel)
                    ->updateAd(
                        $this->facebookAd->fb_ad_id,
                        $this->facebookAd->title_format,
                        $this->facebookAd->fb_status->value
                    );
            } catch (\FacebookAds\Http\Exception\RequestException $th) {
                throw new \Exception($th->getErrorUserMessage() ?? $th->getMessage());
            }
        }

        // publish ad to facebook
        if (! $this->facebookAd->fb_ad_id) {
            try {
                $ad = FacebookAdService::resolve($channel)->publishAd(
                    $this->facebookAd->adset->fb_adset_id,
                    $channel->channelFacebook->page_id,
                    $this->facebookAd->adset->campaign->ad_account_id,
                    $this->facebookAd
                );
            } catch (\FacebookAds\Http\Exception\RequestException $th) {
                throw new \Exception($th->getErrorUserMessage() ?? $th->getMessage());
            }
        }

        $this->facebookAd->fb_ad_id = $ad['id'] ?? $this->facebookAd->fb_ad_id;
        $this->facebookAd->save();

        $this->facebookAd->setProcessToComplete();
        return $this->facebookAd->fresh();
    }

    public function duplicate(FacebookAdset $facebookAdset = null)
    {
        if ($this->facebookAd->fb_ad_id) {
            $ad = FacebookAdService::resolve($this->facebookAd->campaign_channel)
                ->duplicateAd(
                    $facebookAdset->fb_set_id ?? $this->facebookAd->adset->fb_adset_id,
                    $this->facebookAd->fb_ad_id
                );

            if (isset($ad['error'])) {
                return $ad;
            }
        }

        $duplicateAd = $this->facebookAd->replicate()->fill([
            'fb_ad_id' => $ad['id'] ?? null,
            'title' => $ad['name'] ?? "{$this->facebookAd->title_format} - Copy"
        ]);

        if($facebookAdset) {
            $duplicateAd->adset_id = $facebookAdset->id;
        }

        $duplicateAd->save();

        $this->facebookAd->Service()->cloneImages($duplicateAd);

        return $duplicateAd;
    }

    public function toggleStatus()
    {
        if (! $this->facebookAd->fb_ad_id) {
            return [
                'error' => true,
                'message' => 'Cannot toggle on/off when Facebook Ad is not yet published'
            ];
        }

        DB::beginTransaction();
        try {
            $this->facebookAd->fb_status = ($this->facebookAd->fb_status->is(FacebookCampaignStatusEnum::PAUSED()))
                ? FacebookCampaignStatusEnum::ACTIVE()
                : FacebookCampaignStatusEnum::PAUSED();
            $this->facebookAd->save();

            DB::commit();
            $facebookAd = $this->facebookAd->fresh();

            if ($this->facebookAd->status->isNot(CampaignInAppStatusEnum::DRAFT())) {
                ProcessFacebookAd::dispatch($facebookAd);
            }

            return $facebookAd;
        } catch (\FacebookAds\Http\Exception\RequestException $th) {
            DB::rollBack();
            return [
                'status' => 'error',
                'message' => $th->getErrorUserMessage() ?? $th->getMessage()
            ];
        }
    }

    public function delete(): bool
    {
        if ($this->facebookAd->fb_ad_id) {
            FacebookAdService::resolve($this->facebookAd->campaign_channel)
                ->deleteAd($this->facebookAd->fb_ad_id);
        }

        return parent::delete();
    }

    public static function createMultipleTest(
        FacebookAdset $adset,
        int $copies
    )
    {
        if($adset->campaign->channel_id != 141) {
            return [
                'error' => true,
                'message' => 'Facebook Campaign does not have Test Channel'
            ];
        }

        $payload = [
            'adset_id' => $adset->id,
            'site_id' => 30,
            'status' => 2,
            'article_id' => 24,
        ];

        $ads = [];

        for ($i=1; $i <= $copies; $i++) {
            $payload['title'] = "{$adset->title} > Test Ad {$i}";

            $ads[] = self::create(
                $adset,
                Site::findOrFail($payload['site_id']),
                CampaignInAppStatusEnum::memberByValue($payload['status']),
                Article::findOrFail($payload['article_id']),
                $payload['title'],
                null,
                null,
                null,
                null,
                null,
                null
            );

        }

        return $ads;

    }
}
