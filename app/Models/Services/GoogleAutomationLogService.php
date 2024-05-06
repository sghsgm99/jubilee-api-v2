<?php

namespace App\Models\Services;

use App\Models\GoogleAutomationLog;
use App\Models\GoogleCampaign;
use App\Models\GoogleRuleAutomation;
use App\Models\Enums\GoogleRuleTypeEnum;

class GoogleAutomationLogService extends ModelService
{
    /**
     * @var GoogleAutomationLog
     */
    private $ggAutomationLog;

    public function __construct(GoogleAutomationLog $ggAutomationLog)
    {
        $this->ggAutomationLog = $ggAutomationLog;
        $this->model = $ggAutomationLog; // required
    }

    public static function create(
        GoogleRuleAutomation $ggRuleAutomation,
        int $changes = 1

    ):GoogleAutomationLog
    {
        switch ($ggRuleAutomation->apply_to) {
            case GoogleRuleTypeEnum::CAMPAIGN:
                $title = $ggRuleAutomation->applys->pluck('title');
                break;
            case GoogleRuleTypeEnum::ADGROUP:
            case GoogleRuleTypeEnum::AD:
                $title = $ggRuleAutomation->adgroup->title;
                break;
            default: 
                $title = '';
                break;
        }

        $ggAutomationLog = new GoogleAutomationLog();
        $ggAutomationLog->google_rule_automation_id = $ggRuleAutomation->id;
        $ggAutomationLog->changes = $changes;
        $ggAutomationLog->description = [
            'action' => $ggRuleAutomation->action, 
            'conditions' => $ggRuleAutomation->ruleConditions, 
            'title' => $title
        ];
        $ggAutomationLog->user_id = auth()->user()->id;
        $ggAutomationLog->account_id = $ggRuleAutomation->account_id;
        $ggAutomationLog->save();

        return $ggAutomationLog;
    }

    public function setNoChanges()
    {
        $this->ggAutomationLog->changes = 2;
        $this->ggAutomationLog->save();
    }
}
