<?php

namespace App\Models;

use App\Models\Enums\FbRuleConditionComparison;
use App\Models\Enums\FbRuleConditionOperator;
use App\Models\Enums\FbRuleConditionTarget;
use App\Models\Services\FacebookRuleConditionService;
use App\Scopes\AccountScope;
use App\Services\FacebookService;
use App\Services\OpenWeatherService;
use App\Traits\BaseAccountModelTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * FacebookRuleAutomationCondition class
 *
 * Fields
 * @property int $id
 * @property int $facebook_rule_automation_id
 * @property FbRuleConditionTarget $target
 * @property FbRuleConditionOperator $logical_operator
 * @property array $conditions
 * @property int $user_id
 * @property int $account_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 *
 * Relationship
 * @property FacebookRuleAutomation $ruleAutomation
 */
class FacebookRuleAutomationCondition extends Model
{
    use BaseAccountModelTrait;
    use SoftDeletes;

    protected $table = 'facebook_rule_automation_conditions';

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'target' => FbRuleConditionTarget::class,
        'logical_operator' => FbRuleConditionOperator::class,
        'conditions' => 'array'
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new AccountScope());
    }

    public function Service(): FacebookRuleConditionService
    {
        return new FacebookRuleConditionService($this);
    }

    public function ruleAutomation()
    {
        return $this->belongsTo(FacebookRuleAutomation::class, 'facebook_rule_automation_id');
    }
}
