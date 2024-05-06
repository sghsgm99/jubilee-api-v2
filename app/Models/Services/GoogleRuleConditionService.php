<?php

namespace App\Models\Services;

use App\Models\GoogleRuleAutomation;
use App\Models\GoogleRuleAutomationCondition;
use Google\Ads\GoogleAds\V15\Enums\CampaignStatusEnum\CampaignStatus;

class GoogleRuleConditionService extends ModelService
{
    /**
     * @var GoogleRuleAutomationCondition
     */
    private $ggRuleCondition;

    public function __construct(GoogleRuleAutomationCondition $ggRuleCondition)
    {
        $this->ggRuleCondition = $ggRuleCondition;
        $this->model = $ggRuleCondition; // required
    }

    public static function create(
        GoogleRuleAutomation $ggRuleAutomation,
        int $target,
        array $conditions
    )
    {
        $ggRuleCondition = new GoogleRuleAutomationCondition();
        $ggRuleCondition->google_rule_automation_id = $ggRuleAutomation->id;
        $ggRuleCondition->target = $target;
        $ggRuleCondition->conditions = $conditions;
        $ggRuleCondition->user_id = $ggRuleAutomation->user_id;
        $ggRuleCondition->account_id = $ggRuleAutomation->account_id;
        $ggRuleCondition->save();

        return $ggRuleCondition;
    }

    public function update(
        int $target,
        array $conditions
    )
    {
        $this->ggRuleCondition->target = $target;
        $this->ggRuleCondition->conditions = $conditions;
        $this->ggRuleCondition->save();

        return $this->ggRuleCondition->fresh();
    }

    public function checkStatusResult($status): ?bool
    {
        if ($this->ggRuleCondition->conditions['rule_status'] === 0
        ) {
            return true;
        }

        if ($this->ggRuleCondition->conditions['rule_status'] === 1
            && $status === CampaignStatus::ENABLED
        ) {
            return true;
        }

        if ($this->ggRuleCondition->conditions['rule_status'] === 2
            && ($status === CampaignStatus::ENABLED || $status === CampaignStatus::PAUSED)
        ) {
            return true;
        }

        return false;
    }
}
