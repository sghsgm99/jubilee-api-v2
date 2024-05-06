<?php

namespace App\Models\Services;

use App\Models\Channel;
use App\Models\Enums\FbRuleActionEnum;
use App\Models\Enums\FbRuleTargetEnum;
use App\Models\Enums\FbRuleConditionTarget;
use App\Models\Enums\FbRuleConditionOperator;
use App\Models\FacebookAd;
use App\Models\FacebookAdset;
use App\Models\FacebookCampaign;
use App\Models\FacebookRuleAutomation;
use App\Models\FacebookRuleAutomationCondition;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FacebookRuleAutomationService extends ModelService
{
    /**
     * @var FacebookRuleAutomation
     */
    private $fbRuleAutomation;

    public function __construct(FacebookRuleAutomation $fbRuleAutomation)
    {
        $this->fbRuleAutomation = $fbRuleAutomation;
        $this->model = $fbRuleAutomation; // required
    }

    /**
     * @throws \Exception
     */
    public static function create(
        string $name,
        FbRuleTargetEnum $target,
        FbRuleActionEnum $action,
        int $hours,
        array $conditions = []
    ): FacebookRuleAutomation
    {
        DB::beginTransaction();
        try {
            $fbRuleAutomation = new FacebookRuleAutomation();
            $fbRuleAutomation->name = $name;
            $fbRuleAutomation->target = $target;
            $fbRuleAutomation->action = $action;
            $fbRuleAutomation->minutes = $hours * 60;
            $fbRuleAutomation->user_id = auth()->user()->id;
            $fbRuleAutomation->account_id = auth()->user()->account_id;
            $fbRuleAutomation->save();

            foreach ($conditions as $condition) {
                $target = array_pull($condition, 'target');
                $logical_operator = array_pull($condition, 'logical_operator');

                FacebookRuleConditionService::create(
                    $fbRuleAutomation,
                    FbRuleConditionTarget::memberByValue($target),
                    FbRuleConditionOperator::memberByValue($logical_operator),
                    $condition
                );
            }

            DB::commit();

            return $fbRuleAutomation;
        } catch (\Throwable $exception) {
            DB::rollBack();
            throw new \Exception('Unable to create rule automation');
        }
    }

    public function update(
        string $name,
        FbRuleTargetEnum $target,
        FbRuleActionEnum $action,
        int $hours,
        array $conditions = []
    ): FacebookRuleAutomation
    {
        $this->fbRuleAutomation->name = $name;
        $this->fbRuleAutomation->target = $target;
        $this->fbRuleAutomation->action = $action;
        $this->fbRuleAutomation->minutes = $hours * 60;
        $this->fbRuleAutomation->save();

        /**
         * deletes the remove conditions
         */
        $ids = array_filter(Arr::pluck($conditions, 'id'));
        $this->fbRuleAutomation->ruleConditions()
            ->whereNotIn('id', $ids)
            ->delete();

        /**
         * insert new conditions
         * also update existing conditions
         */
        foreach ($conditions as $condition) {
            $id = array_pull($condition, 'id');
            $target = array_pull($condition, 'target');
            $logical_operator = array_pull($condition, 'logical_operator');

            if (! $id) {
                FacebookRuleConditionService::create(
                    $this->fbRuleAutomation,
                    FbRuleConditionTarget::memberByValue($target),
                    FbRuleConditionOperator::memberByValue($logical_operator),
                    $condition
                );
            } else {
                $ruleCondition = $this->fbRuleAutomation->ruleConditions()
                    ->where('id', $id)
                    ->first();

                $ruleCondition->Service()->update(
                    FbRuleConditionTarget::memberByValue($target),
                    FbRuleConditionOperator::memberByValue($logical_operator),
                    $condition
                );
            }
        }

        return $this->fbRuleAutomation->fresh();
    }

    public function delete(): bool
    {
        foreach ($this->fbRuleAutomation->ruleConditions as $ruleCondition) {
            $ruleCondition->Service()->delete();
        }

        return parent::delete();
    }

    public function processAutomation()
    {
        /** @var FacebookCampaign $fbCampaigns */
        $fbCampaigns = $this->fbRuleAutomation->facebookCampaigns()
            ->whereNotNull('fb_campaign_id');

        if (! $fbCampaigns->count()) {
            throw new \InvalidArgumentException("Rule automation doesn't have published campaigns");
        }

        // use to store data for handling automation that has duration
        $target_data = [];

        if ($this->fbRuleAutomation->target->is(FbRuleTargetEnum::CAMPAIGNS())) {
            Log::info('Campaign FacebookRuleAutomation ' . date('Y-m-d H:i:s'));

            foreach ($fbCampaigns->whereRuleAction($this->fbRuleAutomation->action)->cursor() as $fbCampaign) {
                /** @var FacebookCampaign $fbCampaign */
                $fbAutomationLog = FacebookAutomationLogService::create($this->fbRuleAutomation, $fbCampaign);

                if (! $this->validateConditions($fbCampaign->channel, $fbCampaign->fb_campaign_id)) {
                    $fbAutomationLog->Service()->setErroredAt('Rule conditions were not met');
                    continue;
                }

                $response = $fbCampaign->Service()->toggleStatus();

                if (isset($response['error'])) {
                    $fbAutomationLog->Service()->setErroredAt($response['message']);
                    continue;
                }

                $target_data[] = [
                    'model' => $fbCampaign->class_name,
                    'id' => $fbCampaign->id
                ];
            }
        }

        if ($this->fbRuleAutomation->target->is(FbRuleTargetEnum::ADSETS())) {
            Log::info('Adset FacebookRuleAutomation ' . date('Y-m-d H:i:s'));

            $fbAdsets = FacebookAdset::whereRuleAction($this->fbRuleAutomation->action)
                ->whereNotNull('fb_adset_id')
                ->whereIn('campaign_id', $fbCampaigns->pluck('id'));

            foreach ($fbAdsets->cursor() as $fbAdset) {
                /** @var FacebookAdset $fbAdset */
                $fbAutomationLog = FacebookAutomationLogService::create($this->fbRuleAutomation, $fbAdset);

                if (! $this->validateConditions($fbAdset->campaign->channel, $fbAdset->fb_adset_id)) {
                    $fbAutomationLog->Service()->setErroredAt('Rule conditions were not met');
                    continue;
                }

                $response = $fbAdset->Service()->toggleStatus();

                if (isset($response['error'])) {
                    $fbAutomationLog->Service()->setErroredAt($response['message']);
                    continue;
                }

                $target_data[] = [
                    'model' => $fbAdset->class_name,
                    'id' => $fbAdset->id
                ];
            }
        }

        if ($this->fbRuleAutomation->target->is(FbRuleTargetEnum::ADS())) {
            Log::info('Ad FacebookRuleAutomation ' . date('Y-m-d H:i:s'));

            $fbAdsetIds = FacebookAdset::query()->has('ads')
                ->whereNotNull('fb_adset_id')
                ->whereIn('campaign_id', $fbCampaigns->pluck('id'));

            $fbAds = FacebookAd::whereRuleAction($this->fbRuleAutomation->action)
                ->whereNotNull('fb_ad_id')
                ->whereIn('adset_id', $fbAdsetIds);

            foreach ($fbAds->cursor() as $fbAd) {
                /** @var FacebookAd $fbAd */
                $fbAutomationLog = FacebookAutomationLogService::create($this->fbRuleAutomation, $fbAd);

                if (! $this->validateConditions($fbAd->campaign_channel, $fbAd->fb_ad_id)) {
                    $fbAutomationLog->Service()->setErroredAt('Rule conditions were not met');
                    continue;
                }

                $response = $fbAd->Service()->toggleStatus();

                if (isset($response['error'])) {
                    $fbAutomationLog->Service()->setErroredAt($response['message']);
                    continue;
                }

                $target_data[] = [
                    'model' => $fbAd->class_name,
                    'id' => $fbAd->id
                ];
            }
        }

        /**
         * handles rule duration
         * creates a DB record that will be manage by ProcessRuleDurationCommand
         */
        if (! empty($this->fbRuleAutomation->minutes) && ! empty($target_data)) {
            Log::info('Creating FacebookRuleDurationService ' . date('Y-m-d H:i:s'));

            FacebookRuleDurationService::create($this->fbRuleAutomation, $target_data);
        }
    }

    private function validateConditions(Channel $channel, string $fb_id)
    {
        $validation = false;

        foreach ($this->fbRuleAutomation->ruleConditions as $condition) {
            /** @var FacebookRuleAutomationCondition $condition */
            $result = 0;

            if ($condition->target->is(FbRuleConditionTarget::GEOGRAPHY())) {
                $result = (int) $condition->Service()->geographyResult();
            }

            if ($condition->target->is(FbRuleConditionTarget::DATE())) {
                if ($condition->conditions['type'] === 'range') {
                    $result = (int) $condition->Service()->dateRangeResult();
                }

                if ($condition->conditions['type'] === 'exact') {
                    $result = (int) $condition->Service()->dateExactResult();
                }
            }

            if ($condition->target->is(FbRuleConditionTarget::REVENUE())) {
                $result = (int) $condition->Service()->revenueResult($channel, $fb_id);
            }

            if ($condition->target->is(FbRuleConditionTarget::VISITORS())) {
                $result = (int) $condition->Service()->visitorResult($channel, $fb_id);
            }

            if ($condition->logical_operator->isNotUndefined()) {
                $validation .= " {$condition->logical_operator->getKey()} {$result}";
                continue;
            }

            $validation .= "{$result}";
        }

        //return eval("return $validation;");
        return $result;
    }
}
