<?php

namespace App\Models\Services;

use App\Models\FacebookAd;
use App\Models\FacebookAdset;
use App\Models\FacebookAutomationLog;
use App\Models\FacebookCampaign;
use App\Models\FacebookRuleAutomation;

class FacebookAutomationLogService extends ModelService
{
    /**
     * @var FacebookAutomationLog
     */
    private $fbAutomationLog;

    public function __construct(FacebookAutomationLog $fbAutomationLog)
    {
        $this->fbAutomationLog = $fbAutomationLog;
        $this->model = $fbAutomationLog; // required
    }

    public static function create(
        FacebookRuleAutomation $fbRuleAutomation,
        FacebookCampaign|FacebookAdset|FacebookAd $model

    ):FacebookAutomationLog
    {
        $fbAutomationLog = new FacebookAutomationLog();
        $fbAutomationLog->loggable_id = $model->id;
        $fbAutomationLog->loggable_type = $model->class_name;
        $fbAutomationLog->facebook_rule_automation_id = $fbRuleAutomation->id;
        $fbAutomationLog->account_id = $fbRuleAutomation->account_id;
        $fbAutomationLog->processed_at = now();
        $fbAutomationLog->save();

        return $fbAutomationLog;
    }

    public function setErroredAt(string $message)
    {
        $this->fbAutomationLog->errored_at = now();
        $this->fbAutomationLog->error_message = $message;
        $this->fbAutomationLog->save();
    }

    public function setCompletedAt()
    {
        $this->fbAutomationLog->completed_at = now();
        $this->fbAutomationLog->save();
    }
}
