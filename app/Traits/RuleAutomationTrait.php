<?php

namespace App\Traits;

use App\Models\Enums\FacebookCampaignStatusEnum;
use App\Models\Enums\FbRuleActionEnum;
use Illuminate\Database\Eloquent\Builder;

/**
 * @method static Builder whereRuleAction(FbRuleActionEnum $value) // scopeWhereRuleAction
 */
trait RuleAutomationTrait
{
    public function scopeWhereRuleAction(Builder $query, FbRuleActionEnum $fbRuleActionEnum)
    {
        if ($fbRuleActionEnum->is(FbRuleActionEnum::OFF())) {
            return $query->whereFbStatus(FacebookCampaignStatusEnum::ACTIVE());
        }

        if ($fbRuleActionEnum->is(FbRuleActionEnum::ON())) {
            return $query->whereFbStatus(FacebookCampaignStatusEnum::PAUSED());
        }

        return $query;
    }
}
