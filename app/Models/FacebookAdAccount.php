<?php

namespace App\Models;

use App\Models\Enums\FacebookBusinessManagerTypeEnum;
use App\Scopes\AccountScope;
use App\Traits\BaseAccountModelTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FacebookAdAccount extends Model
{
    use HasFactory;
    use BaseAccountModelTrait;
    use SoftDeletes;

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'business_manager_type' => FacebookBusinessManagerTypeEnum::class
    ];

    protected $fillable = [
        'name',
        'ad_account_id',
        'act_ad_account_id',
        'business_manager_id',
        'business_manager_type',
        'account_id'
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new AccountScope());
    }

    public function scopeSearch(
        Builder $query,
        string $search = null,
        string $business_manager_id = null
    )
    {
        if ($search) {
            $query->where(function ($q) use($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('ad_account_id', 'like', '%'.$search.'%');
            });
        }

        // filter business manager id
        $business_manager_id = $business_manager_id ?? config('facebook.parent_bm.business_manager_id');
        $query->where('business_manager_id', $business_manager_id);

        return $query;
    }
}
