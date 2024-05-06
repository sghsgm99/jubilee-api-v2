<?php

namespace App\Models\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\GoogleAd;
use App\Models\GoogleAdgroup;
use App\Services\GoogleAdService;
use App\Jobs\ProcessGoogleAd;
use Google\Ads\GoogleAds\Lib\V15\GoogleAdsException;
use Google\ApiCore\ApiException;
use Google\Ads\GoogleAds\V15\Enums\AdGroupAdStatusEnum\AdGroupAdStatus;

class GoogleAdModelService extends ModelService
{
    private $googleAd;

    public function __construct(GoogleAd $googleAd)
    {
        $this->googleAd = $googleAd;
        $this->model = $googleAd; // required
    }

    public static function create(
        GoogleAdgroup $google_adgroup,
        int $type,
        int $status,
        array $data = null
    )
    {
        try {
            DB::beginTransaction();

            $googleAd = new GoogleAd;
            $googleAd->adgroup_id = $google_adgroup->id;
            $googleAd->type = $type;
            $googleAd->status = $status;
            $googleAd->user_id = Auth::user()->id;
            $googleAd->account_id = Auth::user()->account_id;
            $googleAd->data = $data;
            $googleAd->save();

            DB::commit();

            ProcessGoogleAd::dispatch($googleAd, 1);

            return $googleAd;
        } catch (\Throwable $th) {
            DB::rollBack();
            return [
                'error' => true,
                'message' => $th->getMessage()
            ];
        }
    }

    public function update(
        int $status,
        array $data
    )
    {
        try {
            DB::beginTransaction();

            $this->googleAd->status = $status;
            $this->googleAd->data = $data;
            $this->googleAd->save();

            DB::commit();
            $googleAd = $this->googleAd->fresh();

            ProcessGoogleAd::dispatch($googleAd, 1);

            return $googleAd;
        } catch (\Throwable $th) {
            DB::rollBack();
            return ['error' => $th->getMessage()];
        }
    }

    public function updateStatus(
        int $status
    )
    {
        try {
            DB::beginTransaction();

            $this->googleAd->status = $status;
            $this->googleAd->save();

            DB::commit();
            $googleAd = $this->googleAd->fresh();

            ProcessGoogleAd::dispatch($googleAd, 2);

            return $googleAd;
        } catch (\Throwable $th) {
            DB::rollBack();
            return ['error' => $th->getMessage()];
        }
    }

    public function processAd()
    {
        $googleAdService = GoogleAdService::resolve($this->googleAd->adgroup->campaign->customer->google_account);
        
        try {
            if ($this->googleAd->gg_ad_id) {
                $result = $googleAdService->updateAd($this->googleAd);

                Log::info($result);
            } else {
                $ggAdId = $googleAdService->createAd($this->googleAd);

                $this->googleAd->gg_ad_id = $ggAdId;
                $this->googleAd->save();
            }
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
            "processAd processed at: %s by: %s%s",
            now()->toDateTimeString(),
            $this->googleAd->gg_ad_id,
            PHP_EOL
        ));

        $this->googleAd->setProcessToComplete();

        return $this->googleAd->fresh();
    }

    public function publishAd()
    {
        $googleAdService = GoogleAdService::resolve($this->googleAd->adgroup->campaign->customer->google_account);

        if ($this->googleAd->gg_ad_id) {
            try {
                $result = "";

                if ($this->googleAd->status == AdGroupAdStatus::REMOVED) {
                    $result = $googleAdService->removeAd(
                        $this->googleAd->adgroup->campaign->customer->customer_id,
                        $this->googleAd->adgroup->gg_adgroup_id,
                        $this->googleAd->gg_ad_id,
                    );
                } else {
                    $result = $googleAdService->updateAdStatus(
                        $this->googleAd->adgroup->campaign->customer->customer_id,
                        $this->googleAd->adgroup->gg_adgroup_id,
                        $this->googleAd->gg_ad_id,
                        $this->googleAd->status
                    );
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
            "publishAd processed at: %s by: %s%s",
            now()->toDateTimeString(),
            $this->googleAd->gg_ad_id,
            PHP_EOL
        ));

        $this->googleAd->setProcessToComplete();
        
        return $this->googleAd->fresh();
    }
}
