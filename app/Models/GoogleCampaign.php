<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Models\Services\GoogleCampaignModelService;
use App\Traits\BaseAccountModelTrait;
use App\Traits\GoogleQueueableTrait;
use Google\Ads\GoogleAds\V15\Enums\CampaignStatusEnum\CampaignStatus;

class GoogleCampaign extends Model
{
    use BaseAccountModelTrait;
    use SoftDeletes;
    use GoogleQueueableTrait;

    protected $table = 'google_campaigns';

    protected $fillable = [
        'gg_campaign_id',
        'title'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'data' => 'array'
    ];

    public function Service()
    {
        return new GoogleCampaignModelService($this);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function customer()
    {
        return $this->belongsTo(GoogleCustomer::class);
    }

    public function adgroup()
    {
        return $this->hasMany(GoogleAdgroup::class, 'campaign_id');
    }

    public function scopeSearch(
        Builder $query,
        string $search = null,
        int $google_account = null,
        int $customer_id = null,
        string $sort = null,
        string $sort_type = null
    )
    {
        if ($search) {
            $query->where('title', 'like', '%'.$search.'%');
        }

        if ($google_account) {
            $query->whereHas('customer', function($q) use($google_account) {
                $q->where('google_account', $google_account);
            });
        }

        if ($customer_id) {
            $query->where('customer_id', $customer_id);
        }

        if ($sort) {
            $query->orderBy($sort, $sort_type);
        }

        return $query;
    }

    public function scopeFilter(
        Builder $query
    )
    {
        return $query->where('status', CampaignStatus::ENABLED)->orWhere('status', CampaignStatus::PAUSED);
    }
}
