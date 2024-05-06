<?php

namespace App\Models;

use App\Scopes\AccountScope;
use App\Traits\BaseAccountModelTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Analytic
 *
 * Database Fields
 * @property int $id
 * @property int $channel_id
 * @property int $account_id
 * @property float $spend
 * @property int $clicks
 * @property int $impressions
 * @property int $reach
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 *
 * Relationships
 * @property Channel $channel
 *
 * Scopes
 * @method static Builder|Analytic search(string $search = null, string $sort = null, string $sort_type = null) // scopeSearch
 */
class Analytic extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BaseAccountModelTrait;


    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new AccountScope());
    }

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    /**
     * @param Builder $query
     * @param string|null $search
     * @param string|null $sort
     * @param string|null $sort_type
     * @return Builder
     */
    public function scopeSearch(
        Builder $query,
        string $search = null,
        string $sort = null,
        string $sort_type = null
    )
    {
        $parent_bm = config('facebook.parent_bm.business_manager_id');
        $query->whereHas('channel', function ($q) use($search, $parent_bm) {
            if ($search) {
                $q->where('title', 'like', '%'.$search.'%');
            }
            $q->whereHas('channelFacebook', function($sub_q) use($parent_bm) {
                $sub_q->where('parent_business_manager_id', $parent_bm);
            });
        });

        if($sort) {
            switch ($sort) {
                case 'channel':
                    $query->orderBy(
                        Channel::select('title')
                        ->whereColumn('id', 'channel_id')
                        ->orderBy('title')
                    , $sort_type);
                    break;

                default:
                    $query->orderBy($sort, $sort_type);
                    break;
            }

        }

        return $query;
    }
}
