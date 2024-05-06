<?php

namespace App\Models\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\GoogleAICampaign;
use App\Services\GoogleAICampaignService;
use App\Jobs\ProcessGoogleAICampaign;
use Google\Ads\GoogleAds\Lib\V15\GoogleAdsException;
use Google\ApiCore\ApiException;
use Google\Ads\GoogleAds\V15\Enums\CampaignStatusEnum\CampaignStatus;

class GoogleAICampaignModelService extends ModelService
{
    private $googleAICampaign;

    public function __construct(GoogleAICampaign $googleAICampaign)
    {
        $this->googleAICampaign = $googleAICampaign;
        $this->model = $googleAICampaign; // required
    }

    public static function create(
        string $title,
        string $base_url,
        float $budget,
        float $bid,
        int $customer_id
    )
    {
        try {            
            DB::beginTransaction();

            $googleAICampaign = new GoogleAICampaign();
            $googleAICampaign->title = $title;
            $googleAICampaign->base_url = $base_url;
            $googleAICampaign->final_url = self::generateURL($title, $base_url);
            $googleAICampaign->budget = $budget;
            $googleAICampaign->bid = $bid;
            $googleAICampaign->customer_id = $customer_id;
            $googleAICampaign->user_id = Auth::user()->id;
            $googleAICampaign->account_id = Auth::user()->account_id;
            $googleAICampaign->save();

            DB::commit();

            ProcessGoogleAICampaign::dispatch($googleAICampaign, 1);

            return $googleAICampaign;
        } catch (\Throwable $th) {
            DB::rollBack();
            return [
                'error' => true,
                'message' => $th->getMessage()
            ];
        }
    }

    public static function generateURL(string $title, string $base_url)
    {
        $keyword = preg_replace('/\s+/', '+', $title);
        $type = str_replace(' ', '', $title);

        switch ($base_url) {
            case 'https://socialsearchtoday.com/':
                return "https://socialsearchtoday.com/pla/1/?s=$keyword&n=3&t=$type&mkt=us&src=dissoc_d2s_xmlb_11250_gdn_socialsearchtoday";
            case 'https://sociallysearching.com/':
                return "https://sociallysearching.com/pla/1/?s=$keyword&n=3&t=$type&mkt=us&src=dissoc_d2s_xmlb_11250_gdn_sociallysearching";
            case 'https://socialsearched.com/':
                return "https://socialsearched.com/pla/1/?s=$keyword&n=3&t=$type&mkt=us&src=dissoc_d2s_xmlb_11250_gdn_socialsearched";
            case 'https://socialsearchhelp.com/':
                return "https://socialsearchhelp.com/pla/1/?s=$keyword&n=3&t=$type&mkt=us&src=dissoc_d2s_xmlb_11250_gdn_socialsearchhelp";
            case 'https://socialsearchit.com/':
                return "https://socialsearchit.com/pla/1/?s=$keyword&n=3&t=$type&mkt=us&src=dissoc_d2s_xmlb_11250_gdn_socialsearchit";
            default:
                break;
        }

        return "";
    }

    public function updateStatus(
        int $status
    )
    {
        try {
            DB::beginTransaction();

            $this->googleAICampaign->status = $status;
            $this->googleAICampaign->save();

            DB::commit();
            $googleAICampaign = $this->googleAICampaign->fresh();

            ProcessGoogleAICampaign::dispatch($googleAICampaign, 2);

            return $googleAICampaign;
        } catch (\Throwable $th) {
            DB::rollBack();
            return [
                'error' => true,
                'message' => $th->getMessage()
            ];
        }
    }

    public function processCampaign()
    {
        $googleAICampaignService = GoogleAICampaignService::resolve($this->googleAICampaign->customer->google_account);

        try {
            $ggCampaignId = $googleAICampaignService->createCampaign($this->googleAICampaign);
            $ggAdgroupId = $googleAICampaignService->createAdGroup($this->googleAICampaign, $ggCampaignId);
            $ggAdId = $googleAICampaignService->createSearchAd($this->googleAICampaign, $ggAdgroupId);

            $this->googleAICampaign->campaign_id = $ggCampaignId;
            $this->googleAICampaign->adgroup_id = $ggAdgroupId;
            $this->googleAICampaign->ad_id = $ggAdId;
            $this->googleAICampaign->status = CampaignStatus::PAUSED;
            $this->googleAICampaign->save();
        } catch (GoogleAdsException $googleAdsException) {
            Log::info(sprintf(
                "Request with ID '%s' has failed.%sGoogle Ads failure details:%s",
                $googleAdsException->getRequestId(),
                PHP_EOL,
                PHP_EOL
            ));

            foreach ($googleAdsException->getGoogleAdsFailure()->getErrors() as $error) {
                Log::info($error->getMessage());
            }

            throw new \Exception($apiException->getMessage());
        } catch (ApiException $apiException) {
            Log::info($apiException->getMessage());

            throw new \Exception($apiException->getMessage());
        } catch (\Throwable $th) {
            throw new \Exception($th->getMessage());
        }

        Log::info(sprintf(
            "processCampaign processed at: %s by: %s%s",
            now()->toDateTimeString(),
            $this->googleAICampaign->gg_campaign_id,
            PHP_EOL
        ));

        $this->googleAICampaign->setProcessToComplete();
        
        return $this->googleAICampaign->fresh();
    }

    public function publishCampaign()
    {
        $googleAICampaignService = GoogleAICampaignService::resolve($this->googleAICampaign->customer->google_account);

        if ($this->googleAICampaign->status > 1) {
            try {
                $result = "";

                if ($this->googleAICampaign->status == CampaignStatus::REMOVED) {
                } else {
                    $result = $googleAICampaignService->updateStatus($this->googleAICampaign);
                }

                Log::info($result);
            } catch (GoogleAdsException $googleAdsException) {
                Log::info(sprintf(
                    "Request with ID '%s' has failed.%sGoogle Ads failure details:%s",
                    $googleAdsException->getRequestId(),
                    PHP_EOL,
                    PHP_EOL
                ));

                foreach ($googleAdsException->getGoogleAdsFailure()->getErrors() as $error) {
                    Log::info($error->getMessage());
                }

                throw new \Exception($apiException->getMessage());
            } catch (ApiException $apiException) {
                Log::info($apiException->getMessage());

                throw new \Exception($apiException->getMessage());
            } catch (\Throwable $th) {
                throw new \Exception($th->getMessage());
            }
        }

        Log::info(sprintf(
            "publishCampaign processed at: %s by: %s%s",
            now()->toDateTimeString(),
            $this->googleAICampaign->id,
            PHP_EOL
        ));

        $this->googleAICampaign->setProcessToComplete();
        
        return $this->googleAICampaign->fresh();
    }
}
