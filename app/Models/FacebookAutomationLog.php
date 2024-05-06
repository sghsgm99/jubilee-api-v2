<?php

namespace App\Models;

use App\Models\Enums\FbRuleActionEnum;
use App\Models\Enums\FbRuleTargetEnum;
use App\Models\Services\FacebookAutomationLogService;
use App\Scopes\AccountScope;
use App\Traits\BaseAccountModelTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * FacebookAutomationLog class
 *
 * Fields
 * @property int $id
 * @property int $facebook_rule_automation_id
 * @property int $loggable_id
 * @property string $loggable_type
 * @property int $account_id
 * @property Carbon $processed_at
 * @property Carbon $errored_at
 * @property Carbon $completed_at
 * @property string $error_message
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 *
 * Relationship
 * @property FacebookCampaign $fbCampaign
 * @property FacebookRuleAutomation $fbRuleAutomation
 */
class FacebookAutomationLog extends Model
{
    use BaseAccountModelTrait;
    use SoftDeletes;

    protected $table = 'facebook_automation_logs';

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'processed_at' => 'datetime',
        'errored_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new AccountScope());
    }

    public function Service(): FacebookAutomationLogService
    {
        return new FacebookAutomationLogService($this);
    }

    public function fbRuleAutomation()
    {
        return $this->belongsTo(FacebookRuleAutomation::class, 'facebook_rule_automation_id');
    }
}
