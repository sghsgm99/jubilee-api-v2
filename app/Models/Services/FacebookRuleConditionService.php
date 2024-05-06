<?php

namespace App\Models\Services;

use App\Models\Channel;
use App\Models\Enums\FbRuleConditionComparison;
use App\Models\Enums\FbRuleConditionOperator;
use App\Models\Enums\FbRuleConditionTarget;
use App\Models\FacebookRuleAutomation;
use App\Models\FacebookRuleAutomationCondition;
use App\Services\FacebookService;
use App\Services\OpenWeatherService;
use Carbon\Carbon;

class FacebookRuleConditionService extends ModelService
{
    /**
     * @var FacebookRuleAutomationCondition
     */
    private $fbRuleCondition;

    public function __construct(FacebookRuleAutomationCondition $fbRuleCondition)
    {
        $this->fbRuleCondition = $fbRuleCondition;
        $this->model = $fbRuleCondition; // required
    }

    public static function create(
        FacebookRuleAutomation $fbRuleAutomation,
        FbRuleConditionTarget $target,
        ?FbRuleConditionOperator $logical_operator,
        array $conditions
    )
    {
        $fbRuleCondition = new FacebookRuleAutomationCondition();
        $fbRuleCondition->facebook_rule_automation_id = $fbRuleAutomation->id;
        $fbRuleCondition->target = $target;
        $fbRuleCondition->logical_operator = $logical_operator ?? null;
        $fbRuleCondition->conditions = $conditions;
        $fbRuleCondition->user_id = $fbRuleAutomation->user_id;
        $fbRuleCondition->account_id = $fbRuleAutomation->account_id;
        $fbRuleCondition->save();

        return $fbRuleCondition;
    }

    public function update(
        FbRuleConditionTarget $target,
        FbRuleConditionOperator $logical_operator,
        array $conditions
    )
    {
        $this->fbRuleCondition->target = $target;
        $this->fbRuleCondition->logical_operator = $logical_operator;
        $this->fbRuleCondition->conditions = $conditions;
        $this->fbRuleCondition->save();

        return $this->fbRuleCondition->fresh();
    }

    public function geographyResult(): ?bool
    {
        if ($this->fbRuleCondition->target->isNot(FbRuleConditionTarget::GEOGRAPHY())) {
            return null;
        }

        $weather = OpenWeatherService::resolve()->getWeather(
            $this->fbRuleCondition->conditions['lat'],
            $this->fbRuleCondition->conditions['long']
        );

        if (!isset($weather['main']['temp'])) {
            return false;
        }

        if ($this->fbRuleCondition->conditions['comparison'] === FbRuleConditionComparison::IS_LESS_THAN
            && $weather['main']['temp'] < $this->fbRuleCondition->conditions['temperature']
        ) {
            return true;
        }

        if ($this->fbRuleCondition->conditions['comparison'] === FbRuleConditionComparison::EQUALS
            && $weather['main']['temp'] === $this->fbRuleCondition->conditions['temperature']
        ) {
            return true;
        }

        if ($this->fbRuleCondition->conditions['comparison'] === FbRuleConditionComparison::IS_GREATER_THAN
            && $weather['main']['temp'] > $this->fbRuleCondition->conditions['temperature']
        ) {
            return true;
        }

        return false;
    }

    public function dateRangeResult(): ?bool
    {
        if ($this->fbRuleCondition->target->isNot(FbRuleConditionTarget::DATE())
            && $this->fbRuleCondition->conditions['type'] === 'range'
        ) {
            return null;
        }

        if (now()->gte(Carbon::parse($this->fbRuleCondition->conditions['from']))
            && now()->lte(Carbon::parse($this->fbRuleCondition->conditions['to']))
        ) {
            return true;
        }

        return false;
    }

    public function dateExactResult(): ?bool
    {
        if ($this->fbRuleCondition->target->isNot(FbRuleConditionTarget::DATE())
            && $this->fbRuleCondition->conditions['type'] === 'exact'
        ) {
            return null;
        }

        if (now()->eq(Carbon::parse($this->fbRuleCondition->conditions['from']))) {
            return true;
        }

        return false;

        // recurring
        // 1 recurring
        // 0 one time
        // weekly
        // 1 weekly
        // 0 daily
    }

    public function revenueResult(Channel $channel, string $facebookId): ?bool
    {
        if ($this->fbRuleCondition->target->isNot(FbRuleConditionTarget::REVENUE())) {
            return null;
        }

        $revenue = FacebookService::resolve($channel)->facebookInsights(
            $this->fbRuleCondition->ruleAutomation->target->getFacebookStepLabel(),
            $facebookId,
            'revenue'
        );

        if ($this->fbRuleCondition->conditions['comparison'] === FbRuleConditionComparison::IS_LESS_THAN
            && $revenue < $this->fbRuleCondition->conditions['spend']
        ) {
            return true;
        }

        if ($this->fbRuleCondition->conditions['comparison'] === FbRuleConditionComparison::EQUALS
            && $revenue === $this->fbRuleCondition->conditions['spend']
        ) {
            return true;
        }

        if ($this->fbRuleCondition->conditions['comparison'] === FbRuleConditionComparison::IS_GREATER_THAN
            && $revenue > $this->fbRuleCondition->conditions['spend']
        ) {
            return true;
        }

        return false;
    }

    public function visitorResult(Channel $channel, string $facebookId): ?bool
    {
        if ($this->fbRuleCondition->target->isNot(FbRuleConditionTarget::VISITORS())) {
            return null;
        }

        $reach = FacebookService::resolve($channel)->facebookInsights(
            $this->fbRuleCondition->ruleAutomation->target->getFacebookStepLabel(),
            $facebookId,
            'visitor'
        );

        if ($this->fbRuleCondition->conditions['comparison'] === FbRuleConditionComparison::IS_LESS_THAN
            && $reach < $this->fbRuleCondition->conditions['visitor_count']
        ) {
            return true;
        }

        if ($this->fbRuleCondition->conditions['comparison'] === FbRuleConditionComparison::EQUALS
            && $reach === $this->fbRuleCondition->conditions['visitor_count']
        ) {
            return true;
        }

        if ($this->fbRuleCondition->conditions['comparison'] === FbRuleConditionComparison::IS_GREATER_THAN
            && $reach > $this->fbRuleCondition->conditions['visitor_count']
        ) {
            return true;
        }

        return false;
    }
}
