<?php

namespace App\Models;

use App\Models\Enums\FbRuleActionEnum;
use App\Models\Enums\FbRuleTargetEnum;
use App\Models\Services\FacebookRuleDurationService;
use App\Scopes\AccountScope;
use App\Traits\BaseAccountModelTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * FacebookRuleDuration class
 *
 * Fields
 * @property int $id
 * @property int $facebook_rule_automation_id
 * @property FbRuleTargetEnum $target
 * @property FbRuleActionEnum $action
 * @property array $data
 * @property int $user_id
 * @property int $account_id
 * @property Carbon $processed_at
 * @property Carbon $end_at
 * @property Carbon $completed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 *
 * Relationship
 * @property User $user
 * @property FacebookRuleAutomation $fbRuleAutomation
 */
class FacebookRuleDuration extends Model
{
    use BaseAccountModelTrait;
    use SoftDeletes;

    protected $table = 'facebook_rule_duration';

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'processed_at' => 'datetime',
        'end_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'target' => FbRuleTargetEnum::class,
        'action' => FbRuleActionEnum::class,
        'data' => 'array'
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new AccountScope());
    }

    public function Service(): FacebookRuleDurationService
    {
        return new FacebookRuleDurationService($this);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function fbRuleAutomation()
    {
        return $this->belongsTo(FacebookRuleAutomation::class, 'facebook_rule_automation_id');
    }
}
