<?php

namespace App\Models\Services;

use App\Models\Enums\FbRuleTargetEnum;
use App\Models\FacebookAd;
use App\Models\FacebookAdset;
use App\Models\FacebookCampaign;
use App\Models\FacebookRuleAutomation;
use App\Models\FacebookRuleDuration;

class FacebookRuleDurationService extends ModelService
{
    /**
     * @var FacebookRuleDuration
     */
    private $fbRuleDuration;

    public function __construct(FacebookRuleDuration $fbRuleDuration)
    {
        $this->fbRuleDuration = $fbRuleDuration;
        $this->model = $fbRuleDuration; // required
    }

    public static function create(FacebookRuleAutomation $fbRuleAutomation, array $target_data): FacebookRuleAutomation
    {
        $fbRuleDuration = new FacebookRuleDuration();
        $fbRuleDuration->facebook_rule_automation_id = $fbRuleAutomation->id;
        $fbRuleDuration->target = $fbRuleAutomation->target;
        $fbRuleDuration->action = $fbRuleAutomation->action;
        $fbRuleDuration->data = $target_data;
        $fbRuleDuration->processed_at = now();
        $fbRuleDuration->end_at = now()->addMinutes($fbRuleAutomation->minutes);
        $fbRuleDuration->user_id = $fbRuleAutomation->user_id;
        $fbRuleDuration->account_id = $fbRuleAutomation->account_id;
        $fbRuleDuration->save();

        return $fbRuleAutomation;
    }

    public function setCompletedAt()
    {
        $this->fbRuleDuration->completed_at = now();
        $this->fbRuleDuration->save();
    }

    public function processRuleDuration()
    {
        foreach ($this->fbRuleDuration->data as $row) {
            if ($this->fbRuleDuration->target->is(FbRuleTargetEnum::CAMPAIGNS()) && $row['model'] === FacebookCampaign::class) {
                /** @var FacebookCampaign $fbCampaign */
                if ($fbCampaign = FacebookCampaign::find($row['id'])) {
                    $fbCampaign->Service()->toggleStatus();
                }
            }

            if ($this->fbRuleDuration->target->is(FbRuleTargetEnum::ADSETS()) && $row['model'] === FacebookAdset::class) {
                /** @var FacebookAdset $fbAdset */
                if ($fbAdset = FacebookAdset::find($row['id'])) {
                    $fbAdset->Service()->toggleStatus();
                }
            }

            if ($this->fbRuleDuration->target->is(FbRuleTargetEnum::ADS()) && $row['model'] === FacebookAd::class) {
                /** @var FacebookAd $fbAd */
                if ($fbAd = FacebookAd::find($row['id'])) {
                    $fbAd->Service()->toggleStatus();
                }
            }
        }

        $this->fbRuleDuration->Service()->setCompletedAt();
    }
}
