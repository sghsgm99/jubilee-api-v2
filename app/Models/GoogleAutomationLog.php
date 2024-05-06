<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Scopes\AccountScope;
use App\Traits\BaseAccountModelTrait;
use App\Models\Services\GoogleAutomationLogService;

class GoogleAutomationLog extends Model
{
    use BaseAccountModelTrait;
    use SoftDeletes;

    protected $table = 'google_automation_logs';

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'description' => 'array'
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new AccountScope());
    }

    public function Service(): GoogleAutomationLogService
    {
        return new GoogleAutomationLogService($this);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function ggRuleAutomation()
    {
        return $this->belongsTo(GoogleRuleAutomation::class, 'google_rule_automation_id');
    }

    public function scopeSearch(
        Builder $query, 
        string $search = null,
        int $rule_id = null,
    ): Builder
    {
        if ($search) {
            $query->where('name', 'like', '%'.$search.'%');
        }

        if ($rule_id){
            $query->where('google_rule_automation_id', '=', $rule_id);
        }

        return $query;
    }
}
