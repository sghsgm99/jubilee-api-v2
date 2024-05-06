<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use App\Models\Enums\FbRuleConditionComparison;
use App\Models\Enums\FbRuleConditionOperator;
use App\Models\Enums\FbRuleConditionTarget;
use App\Models\Services\GoogleRuleConditionService;
use App\Scopes\AccountScope;
use App\Traits\BaseAccountModelTrait;

class GoogleRuleAutomationCondition extends Model
{
    use BaseAccountModelTrait;
    use SoftDeletes;

    protected $table = 'google_rule_automation_conditions';

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'conditions' => 'array'
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new AccountScope());
    }

    public function Service(): GoogleRuleConditionService
    {
        return new GoogleRuleConditionService($this);
    }

    public function ruleAutomation()
    {
        return $this->belongsTo(GoogleRuleAutomation::class, 'google_rule_automation_id');
    }
}
