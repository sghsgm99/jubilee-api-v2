<?php

namespace App\Models\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\GoogleAd;
use App\Models\GoogleAdgroup;
use App\Models\GoogleCampaign;
use App\Services\GoogleAdGroupService;
use App\Jobs\ProcessGoogleAdgroup;
use Google\Ads\GoogleAds\Lib\V15\GoogleAdsException;
use Google\ApiCore\ApiException;
use Google\Ads\GoogleAds\V15\Enums\AdGroupStatusEnum\AdGroupStatus;

class GoogleAdgroupModelService extends ModelService
{
    private $googleAdgroup;

    public function __construct(GoogleAdgroup $googleAdgroup)
    {
        $this->googleAdgroup = $googleAdgroup;
        $this->model = $googleAdgroup; // required
    }

    public static function create(
        GoogleCampaign $google_campaign,
        string $title,
        float $bid,
        int $type,
        int $status,
        array $data = null
    )
    {
        try {
            DB::beginTransaction();

            $googleAdgroup = new GoogleAdgroup;
            $googleAdgroup->campaign_id = $google_campaign->id;
            $googleAdgroup->title = $title;
            $googleAdgroup->bid = $bid;
            $googleAdgroup->type = $type;
            $googleAdgroup->status = $status;
            $googleAdgroup->data = $data;
            $googleAdgroup->user_id = Auth::user()->id;
            $googleAdgroup->account_id = Auth::user()->account_id;
            $googleAdgroup->save();

            DB::commit();

            ProcessGoogleAdgroup::dispatch($googleAdgroup, 1);

            return $googleAdgroup;
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
        float $bid,
        int $status,
        array $data = null
    )
    {
        try {
            DB::beginTransaction();

            $this->googleAdgroup->title = $title;
            $this->googleAdgroup->bid = $bid;
            $this->googleAdgroup->status = $status;
            $this->googleAdgroup->data = $data;
            $this->googleAdgroup->save();

            DB::commit();
            $googleAdgroup = $this->googleAdgroup->fresh();

            ProcessGoogleAdgroup::dispatch($googleAdgroup, 1);

            return $googleAdgroup;
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

            $this->googleAdgroup->status = $status;
            $this->googleAdgroup->save();

            DB::commit();
            $googleAdgroup = $this->googleAdgroup->fresh();

            ProcessGoogleAdgroup::dispatch($googleAdgroup, 2);

            return $googleAdgroup;
        } catch (\Throwable $th) {
            DB::rollBack();
            return [
                'error' => true,
                'message' => $th->getMessage()
            ];
        }
    }

    public function updateAdsStatus(
        int $status
    )
    {
        try {
            DB::beginTransaction();

            $idsArray = $this->googleAdgroup->ad()->get()->pluck('id')->toArray();
            GoogleAd::whereIn('id', $idsArray)->update(['status' => $status]);

            DB::commit();

            ProcessGoogleAdgroup::dispatch($this->googleAdgroup, 3, $status);

            return count($idsArray);
        } catch (\Throwable $th) {
            DB::rollBack();

            Log::info($th->getMessage());

            return [
                'error' => true,
                'message' => $th->getMessage()
            ];
        }
    }

    public function updateAdsStatusEx(
        int $status,
        array $result
    )
    {
        try {
            DB::beginTransaction();

            GoogleAd::whereIn('id', $result['ids'])->update(['status' => $status]);

            DB::commit();

            ProcessGoogleAdgroup::dispatch($this->googleAdgroup, 4, $status, $result['gg_ids']);

            return;
        } catch (\Throwable $th) {
            DB::rollBack();

            Log::info($th->getMessage());

            return [
                'error' => true,
                'message' => $th->getMessage()
            ];
        }
    }

    public function processAdgroup()
    {
        $googleAdGroupService = GoogleAdGroupService::resolve($this->googleAdgroup->campaign->customer->google_account);

        try {
            if ($this->googleAdgroup->gg_adgroup_id) {
                $result = $googleAdGroupService->updateAdGroup($this->googleAdgroup);

                Log::info($result);
            } else {
                $ggAdgroupId = $googleAdGroupService->createAdGroup($this->googleAdgroup);

                $this->googleAdgroup->gg_adgroup_id = $ggAdgroupId;
                $this->googleAdgroup->save();
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
            "processAdgroup processed at: %s by: %s%s",
            now()->toDateTimeString(),
            $this->googleAdgroup->gg_adgroup_id,
            PHP_EOL
        ));

        $this->googleAdgroup->setProcessToComplete();

        return $this->googleAdgroup->fresh();
    }

    public function publishAdgroup()
    {
        $googleAdGroupService = GoogleAdGroupService::resolve($this->googleAdgroup->campaign->customer->google_account);

        if ($this->googleAdgroup->gg_adgroup_id) {
            try {
                $result = "";

                if ($this->googleAdgroup->status == AdGroupStatus::REMOVED) {
                    $result = $googleAdGroupService->removeAdGroup(
                        $this->googleAdgroup->campaign->customer->customer_id,
                        $this->googleAdgroup->gg_adgroup_id
                    );
                } else {
                    $result = $googleAdGroupService->updateAdGroupStatus(
                        $this->googleAdgroup->campaign->customer->customer_id,
                        $this->googleAdgroup->gg_adgroup_id,
                        $this->googleAdgroup->status
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
            "publishAdgroup processed at: %s by: %s%s",
            now()->toDateTimeString(),
            $this->googleAdgroup->gg_adgroup_id,
            PHP_EOL
        ));

        $this->googleAdgroup->setProcessToComplete();

        return $this->googleAdgroup->fresh();
    }

    public function delete(): bool
    {
        if ($this->googleAdgroup->ad->count() > 0) {
            abort('403', 'Cannot delete Ad Group if ad are still available');
        }

        return parent::delete();
    }

    public function publishAdgroupAd(int $status)
    {
        $googleAdGroupService = GoogleAdGroupService::resolve($this->googleAdgroup->campaign->customer->google_account);

        try {
            $result = "";
            $ggIdsArray = $this->googleAdgroup->ad()->get()->pluck('gg_ad_id')->toArray();

            $result = $googleAdGroupService->updateAdGroupAdStatus(
                $this->googleAdgroup->campaign->customer->customer_id,
                $this->googleAdgroup->gg_adgroup_id,
                $ggIdsArray,
                $status
            );

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

        Log::info(sprintf(
            "publishAdgroupAd processed at: %s by: %s%s",
            now()->toDateTimeString(),
            $this->googleAdgroup->gg_adgroup_id,
            PHP_EOL
        ));

        $this->googleAdgroup->setProcessToComplete();

        return $this->googleAdgroup->fresh();
    }

    public function publishAdgroupAdEx(int $status, array $ggIdsArray)
    {
        $googleAdGroupService = GoogleAdGroupService::resolve($this->googleAdgroup->campaign->customer->google_account);

        try {
            $result = "";

            $result = $googleAdGroupService->updateAdGroupAdStatus(
                $this->googleAdgroup->campaign->customer->customer_id,
                $this->googleAdgroup->gg_adgroup_id,
                $ggIdsArray,
                $status
            );

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

        Log::info(sprintf(
            "publishAdgroupAd processed at: %s by: %s%s",
            now()->toDateTimeString(),
            $this->googleAdgroup->gg_adgroup_id,
            PHP_EOL
        ));

        $this->googleAdgroup->setProcessToComplete();

        return $this->googleAdgroup->fresh();
    }
}
