<?php

namespace App\Models\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\GoogleCampaign;
use App\Services\GoogleCampaignService;
use App\Jobs\ProcessGoogleCampaign;
use Google\Ads\GoogleAds\Lib\V15\GoogleAdsException;
use Google\ApiCore\ApiException;
use Google\Ads\GoogleAds\V15\Enums\CampaignStatusEnum\CampaignStatus;

class GoogleCampaignModelService extends ModelService
{
    private $googleCampaign;

    public function __construct(GoogleCampaign $googleCampaign)
    {
        $this->googleCampaign = $googleCampaign;
        $this->model = $googleCampaign; // required
    }

    public static function create(
        string $title,
        string $description = null,
        int $customer_id,
        float $budget,
        int $location,
        int $type,
        int $status,
        array $data = null
    )
    {
        try {            
            DB::beginTransaction();

            $googleCampaign = new GoogleCampaign();
            $googleCampaign->title = $title;
            $googleCampaign->description = $description;
            $googleCampaign->customer_id = $customer_id;
            $googleCampaign->budget = $budget;
            $googleCampaign->location = $location;
            $googleCampaign->type = $type;
            $googleCampaign->status = $status;
            $googleCampaign->data = $data;
            $googleCampaign->user_id = Auth::user()->id;
            $googleCampaign->account_id = Auth::user()->account_id;
            $googleCampaign->save();

            DB::commit();

            ProcessGoogleCampaign::dispatch($googleCampaign, 1);

            return $googleCampaign;
        } catch (\Throwable $th) {
            DB::rollBack();
            return [
                'error' => true,
                'message' => $th->getMessage()
            ];
        }
    }

    public function update(
        string $title,
        string $description = null,
        float $budget,
        int $location,
        int $status,
        array $data = null
    )
    {
        try {
            DB::beginTransaction();

            $this->googleCampaign->title = $title;
            $this->googleCampaign->description = $description;
            $this->googleCampaign->budget = $budget;
            $this->googleCampaign->location = $location;
            $this->googleCampaign->status = $status;
            $this->googleCampaign->data = $data;
            $this->googleCampaign->save();

            DB::commit();
            $googleCampaign = $this->googleCampaign->fresh();

            ProcessGoogleCampaign::dispatch($googleCampaign, 1);

            return $googleCampaign;
        } catch (\Throwable $th) {
            DB::rollBack();
            return [
                'error' => true,
                'message' => $th->getMessage()
            ];
        }
    }

    public function updateStatus(
        int $status
    )
    {
        try {
            DB::beginTransaction();

            $this->googleCampaign->status = $status;
            $this->googleCampaign->save();

            DB::commit();
            $googleCampaign = $this->googleCampaign->fresh();

            ProcessGoogleCampaign::dispatch($googleCampaign, 2);

            return $googleCampaign;
        } catch (\Throwable $th) {
            DB::rollBack();
            return [
                'error' => true,
                'message' => $th->getMessage()
            ];
        }
    }

    public function delete(): bool
    {
        if ($this->googleCampaign->adgroup->count() > 0) {
            abort('403', 'Cannot delete Campaign if adgroup are still available');
        }

        /*if ($this->googleCampaign->gg_campaign_id) {
            GoogleCampaignService::resolve($this->googleCampaign->customer->google_account)->deleteCampaign($this->googleCampaign->gg_campaign_id);
        }*/

        return parent::delete();
    }

    public function processCampaign()
    {
        $googleCampaignService = GoogleCampaignService::resolve($this->googleCampaign->customer->google_account);

        try {
            if ($this->googleCampaign->gg_campaign_id) {
                $result = $googleCampaignService->updateCampaign($this->googleCampaign);

                Log::info($result);
            } else {
                $ggCampaignId = $googleCampaignService->createCampaign($this->googleCampaign);

                $this->googleCampaign->gg_campaign_id = $ggCampaignId;
                $this->googleCampaign->save();
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
            "processCampaign processed at: %s by: %s%s",
            now()->toDateTimeString(),
            $this->googleCampaign->gg_campaign_id,
            PHP_EOL
        ));

        $this->googleCampaign->setProcessToComplete();
        
        return $this->googleCampaign->fresh();
    }

    public function publishCampaign()
    {
        $googleCampaignService = GoogleCampaignService::resolve($this->googleCampaign->customer->google_account);

        if ($this->googleCampaign->gg_campaign_id) {
            try {
                $result = "";

                if ($this->googleCampaign->status == CampaignStatus::REMOVED) {
                    $result = $googleCampaignService->removeCampaign(
                        $this->googleCampaign->customer->customer_id,
                        $this->googleCampaign->gg_campaign_id
                    );
                } else {
                    $result = $googleCampaignService->updateCampaignStatus(
                        $this->googleCampaign->customer->customer_id,
                        $this->googleCampaign->gg_campaign_id,
                        $this->googleCampaign->status
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
            "publishCampaign processed at: %s by: %s%s",
            now()->toDateTimeString(),
            $this->googleCampaign->gg_campaign_id,
            PHP_EOL
        ));

        $this->googleCampaign->setProcessToComplete();
        
        return $this->googleCampaign->fresh();
    }

}
