<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Scopes\AccountScope;
use App\Traits\BaseAccountModelTrait;
use App\Models\Services\GoogleRuleAutomationService;

class GoogleRuleAutomation extends Model
{
    use BaseAccountModelTrait;
    use SoftDeletes;

    protected $table = 'google_rule_automations';

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new AccountScope());
    }

    public function Service(): GoogleRuleAutomationService
    {
        return new GoogleRuleAutomationService($this);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function customer()
    {
        return $this->belongsTo(GoogleCustomer::class, 'apply_to_id');
    }

    public function campaign()
    {
        return $this->belongsTo(GoogleCampaign::class, 'apply_to_id');
    }

    public function adgroup()
    {
        return $this->belongsTo(GoogleAdgroup::class, 'apply_to_id');
    }

    public function ruleConditions()
    {
        return $this->hasMany(GoogleRuleAutomationCondition::class, 'google_rule_automation_id')->orderBy('id');
    }

    public function applys()
    {
        return $this->belongsToMany(GoogleCampaign::class, 'google_rule_automation_applys', 'google_rule_automation_id', 'apply_to_id')->withTimestamps();
    }

    public function scopeSearch(Builder $query, string $search = null): Builder
    {
        if ($search) {
            $query->where('name', 'like', '%'.$search.'%');
        }

        return $query;
    }
}
