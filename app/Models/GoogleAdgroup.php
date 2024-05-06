<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Models\Services\GoogleAdgroupModelService;
use App\Traits\BaseAccountModelTrait;
use App\Traits\GoogleQueueableTrait;

class GoogleAdgroup extends Model
{
    use BaseAccountModelTrait;
    use SoftDeletes;
    use GoogleQueueableTrait;

    protected $table = 'google_adgroups';

    protected $fillable = [
        'gg_adgroup_id',
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
        return new GoogleAdgroupModelService($this);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function campaign()
    {
        return $this->belongsTo(GoogleCampaign::class, 'campaign_id');
    }

    public function ad()
    {
        return $this->hasMany(GoogleAd::class, 'adgroup_id');
    }

    public function scopeSearch(
        Builder $query,
        string $search = null,
        int $campaign_id = null,
        string $sort = null,
        string $sort_type = null
    )
    {
        if ($search) {
            $query->where('title', 'like', '%'.$search.'%');
        }

        if ($campaign_id){
            $query->where('campaign_id', '=', $campaign_id);
        }

        if ($sort) {
            $query->orderBy($sort, $sort_type);
        }

        return $query;
    }
}
