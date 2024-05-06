<?php

namespace App\Models\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\GoogleRuleAutomation;
use App\Models\Enums\GoogleRuleTypeEnum;
use App\Models\GoogleCampaign;
use App\Jobs\ProcessGoogleAutomation;
use App\Services\GoogleCampaignService;

class GoogleRuleAutomationService extends ModelService
{
    /**
     * @var GoogleRuleAutomation
     */
    private $ggRuleAutomation;

    public function __construct(GoogleRuleAutomation $ggRuleAutomation)
    {
        $this->ggRuleAutomation = $ggRuleAutomation;
        $this->model = $ggRuleAutomation; // required
    }

    /**
     * @throws \Exception
     */
    public static function create(
        string $name,
        int $apply_to,
        int $apply_to_id,
        int $action,
        int $frequency,
        array $conditions = []
    ): GoogleRuleAutomation
    {
        DB::beginTransaction();

        try {
            $ggRuleAutomation = new GoogleRuleAutomation();
            $ggRuleAutomation->name = $name;
            $ggRuleAutomation->apply_to = $apply_to;
            $ggRuleAutomation->apply_to_id = $apply_to_id;
            $ggRuleAutomation->action = $action;
            $ggRuleAutomation->frequency = $frequency;
            $ggRuleAutomation->user_id = auth()->user()->id;
            $ggRuleAutomation->account_id = auth()->user()->account_id;
            $ggRuleAutomation->save();

            foreach ($conditions as $condition) {
                $target = array_pull($condition, 'target');

                GoogleRuleConditionService::create(
                    $ggRuleAutomation,
                    $target,
                    $condition
                );
            }

            DB::commit();

            return $ggRuleAutomation;
        } catch (\Throwable $exception) {
            DB::rollBack();
            throw new \Exception('Unable to create rule automation');
        }
    }

    public function update(
        string $name,
        int $action,
        int $frequency,
        array $conditions = []
    ): GoogleRuleAutomation
    {
        $this->ggRuleAutomation->name = $name;
        $this->ggRuleAutomation->action = $action;
        $this->ggRuleAutomation->frequency = $frequency;
        $this->ggRuleAutomation->touch();

        $ids = array_filter(Arr::pluck($conditions, 'id'));
        $this->ggRuleAutomation->ruleConditions()
            ->whereNotIn('id', $ids)
            ->delete();

        foreach ($conditions as $condition) {
            $id = array_pull($condition, 'id');
            $target = array_pull($condition, 'target');

            if (!$id) {
                GoogleRuleConditionService::create(
                    $this->ggRuleAutomation,
                    $target,
                    $condition
                );
            } else {
                $ruleCondition = $this->ggRuleAutomation->ruleConditions()
                    ->where('id', $id)
                    ->first();

                $ruleCondition->Service()->update(
                    $target,
                    $condition
                );
            }
        }

        return $this->ggRuleAutomation->fresh();
    }

    public function syncApplys(array $apply_ids = [])
    {
        if (empty($apply_ids)) {
            return $this->ggRuleAutomation->applys()->detach();
        }

        return $this->ggRuleAutomation->applys()->sync($apply_ids);
    }

    public function updateStatus(
        int $status
    )
    {
        $this->ggRuleAutomation->status = $status;
        $this->ggRuleAutomation->save();

        return $this->ggRuleAutomation->fresh();
    }

    public function processAutomation($freq_label = 'RUN')
    {
        switch ($this->ggRuleAutomation->apply_to) {
            case GoogleRuleTypeEnum::CAMPAIGN:
                $ggObjects = $this->ggRuleAutomation->campaign()->whereNotNull('gg_campaign_id');
                $label = GoogleRuleTypeEnum::CAMPAIGN()->getLabel();
                break;
            case GoogleRuleTypeEnum::ADGROUP:
                $ggObjects = $this->ggRuleAutomation->adgroup()->whereNotNull('gg_adgroup_id');
                $label = GoogleRuleTypeEnum::ADGROUP()->getLabel();
                break;
            case GoogleRuleTypeEnum::AD:
                $ggObjects = $this->ggRuleAutomation->adgroup()->whereNotNull('gg_adgroup_id');
                $label = GoogleRuleTypeEnum::ADGROUP()->getLabel() . 'for ads';
                break;
            default:
                break;
        }

        if (! $ggObjects->count()) {
            throw new \InvalidArgumentException("Rule automation doesn't have published ".$label);
        }

        foreach ($ggObjects->cursor() as $ggObject) {
            $ggAutomationLog = GoogleAutomationLogService::create($this->ggRuleAutomation);

            if (! $this->validateConditions($ggObject)) {
                $ggAutomationLog->Service()->setNoChanges();
                continue;
            }

            if ($this->ggRuleAutomation->apply_to == GoogleRuleTypeEnum::AD) {
                $response = $ggObject->Service()->updateAdsStatus($this->ggRuleAutomation->action);
            } else {
                $response = $ggObject->Service()->updateStatus($this->ggRuleAutomation->action);
            }
        }
        
        Log::info(sprintf(
            "GoogleRuleAutomation(%s) process at: %s by: %s",
            $freq_label, 
            now()->toDateTimeString(),
            $this->ggRuleAutomation->name
        ));
    }

    private function validateConditions($ggObject)
    {
        $validation = true;

        foreach ($this->ggRuleAutomation->ruleConditions as $condition) {
            if ($condition->target == 0) {
                $result = $condition->Service()->checkStatusResult($ggObject->status);
            }

            $validation &= $result;
        }

        return $result;
    }

    public function processAutomationBulk($freq_label = 'RUN')
    {
        switch ($this->ggRuleAutomation->apply_to) {
            case GoogleRuleTypeEnum::CAMPAIGN:
                break;
            case GoogleRuleTypeEnum::ADGROUP:
                break;
            case GoogleRuleTypeEnum::AD:
                $ggObjects = $this->ggRuleAutomation->adgroup()->whereNotNull('gg_adgroup_id');
                $label = GoogleRuleTypeEnum::ADGROUP()->getLabel() . 'for ads';
                break;
            default:
                break;
        }

        if (! $ggObjects->count()) {
            throw new \InvalidArgumentException("Rule automation doesn't have published ".$label);
        }

        $result = [
            'ids' => [],
            'gg_ids' => []
        ];

        foreach ($ggObjects->cursor() as $ggObject) {
            if ($this->ggRuleAutomation->apply_to == GoogleRuleTypeEnum::AD) {
                $ggChildObjects = $ggObject->ad()->whereNotNull('gg_ad_id');

                if (! $ggChildObjects->count()) {
                    continue;
                }

                foreach ($ggChildObjects->cursor() as $ggChildObject) {
                    if (! $this->validateConditions($ggChildObject)) {
                        continue;
                    }

                    $result['ids'][] = $ggChildObject->id;
                    $result['gg_ids'][] = $ggChildObject->gg_ad_id;
                }

                if (count($result['ids']) > 0)
                    $response = $ggObject->Service()->updateAdsStatusEx($this->ggRuleAutomation->action, $result);
                
                GoogleAutomationLogService::create($this->ggRuleAutomation, count($result['ids']));
            }
        }
        
        Log::info(sprintf(
            "GoogleRuleAutomation(%s) process at: %s by: %s",
            $freq_label, 
            now()->toDateTimeString(),
            $this->ggRuleAutomation->name
        ));
    }

    public function processAutomationEx($freq_label = 'RUN')
    {
        switch ($this->ggRuleAutomation->apply_to) {
            case GoogleRuleTypeEnum::CAMPAIGN:
                $ggObjects = $this->ggRuleAutomation->applys()->whereNotNull('gg_campaign_id');
                $label = GoogleRuleTypeEnum::CAMPAIGN()->getLabel();
                break;
            default:
                break;
        }

        if (! $ggObjects->count()) {
            throw new \InvalidArgumentException("Rule automation doesn't have published ".$label);
        }

        $result = [
            'ids' => [],
            'gg_ids' => []
        ];
        
        foreach ($ggObjects->cursor() as $ggObject) {
            if (! $this->validateConditions($ggObject)) {
                continue;
            }

            $result['ids'][] = $ggObject->id;
            $result['gg_ids'][] = $ggObject->gg_campaign_id;
        }

        if (count($result['ids']) > 0)
            $response = $this->updateMultipleStatus($result);
        
        GoogleAutomationLogService::create($this->ggRuleAutomation, count($result['ids']));
        
        Log::info(sprintf(
            "GoogleRuleAutomation(%s) process at: %s by: %s",
            $freq_label, 
            now()->toDateTimeString(),
            $this->ggRuleAutomation->name
        ));
    }

    private function updateMultipleStatus(
        array $result
    )
    {
        try {
            DB::beginTransaction();

            GoogleCampaign::whereIn('id', $result['ids'])->update(['status' => $this->ggRuleAutomation->action]);

            DB::commit();

            ProcessGoogleAutomation::dispatch($this->ggRuleAutomation, $result['gg_ids']);

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

    public function processGoogleAutomation(array $ggIdsArray)
    {
        $googleCampaignService = GoogleCampaignService::resolve($this->ggRuleAutomation->customer->google_account);
        
        try {
            $result = $googleCampaignService->updateMultipleCampaignsStatus(
                $this->ggRuleAutomation->customer->customer_id,
                $ggIdsArray,
                $this->ggRuleAutomation->action
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

        //$this->googleCampaign->setProcessToComplete();
        
        return $this->ggRuleAutomation->fresh();
    }
}
