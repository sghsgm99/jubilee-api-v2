<?php

namespace App\Models;

use App\Models\Enums\FbRuleActionEnum;
use App\Models\Enums\FbRuleTargetEnum;
use App\Models\Services\FacebookRuleAutomationService;
use App\Scopes\AccountScope;
use App\Traits\BaseAccountModelTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * FacebookRuleAutomation class
 *
 * Fields
 * @property int $id
 * @property string $name
 * @property FbRuleTargetEnum $target
 * @property FbRuleActionEnum $action
 * @property int $minutes
 * @property int $user_id
 * @property int $account_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 *
 * @property-read int|null $hours // getHoursAttribute
 *
 * Relationship
 * @property FacebookCampaign $facebookCampaigns
 * @property FacebookRuleAutomationCondition $ruleConditions
 *
 * Scopes
 * @method static Builder|FacebookRuleAutomation search(string $search) // scopeSearch
 */
class FacebookRuleAutomation extends Model
{
    use BaseAccountModelTrait;
    use SoftDeletes;

    protected $table = 'facebook_rule_automations';

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'target' => FbRuleTargetEnum::class,
        'action' => FbRuleActionEnum::class,
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new AccountScope());
    }

    public function getHoursAttribute()
    {
        return $this->attributes['minutes'] / 60;
    }

    public function Service(): FacebookRuleAutomationService
    {
        return new FacebookRuleAutomationService($this);
    }

    public function facebookCampaigns()
    {
        return $this->hasMany(FacebookCampaign::class, 'facebook_rule_automation_id');
    }

    public function ruleConditions()
    {
        return $this->hasMany(FacebookRuleAutomationCondition::class, 'facebook_rule_automation_id')
            ->orderBy('id');
    }

    public function scopeSearch(Builder $query, string $search = null): Builder
    {
        if ($search) {
            $query->where('name', 'like', '%'.$search.'%');
        }

        return $query;
    }
}
