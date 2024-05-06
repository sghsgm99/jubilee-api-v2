<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Models\Services\GoogleAdModelService;
use App\Traits\BaseAccountModelTrait;
use App\Models\Enums\StorageDiskEnum;
use App\Traits\GoogleQueueableTrait;

class GoogleAd extends Model
{
    use HasFactory;
    use BaseAccountModelTrait;
    use SoftDeletes;
    use GoogleQueueableTrait;

    protected $table = 'google_ads';

    protected $fillable = [
        'gg_ad_id'
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
        return new GoogleAdModelService($this);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function adgroup()
    {
        return $this->belongsTo(GoogleAdgroup::class, 'adgroup_id');
    }

    public function scopeSearch(
        Builder $query,
        string $search = null,
        int $adgroup_id = null,
        string $sort = null,
        string $sort_type = null
    )
    {
        if ($search) {
            $query->where('title', 'like', '%'.$search.'%');
        }

        if ($adgroup_id){
            $query->where('adgroup_id', '=', $adgroup_id);
        }

        if ($sort) {
            if ($sort == 'ad') 
                $query->orderBy('data->headlines', $sort_type);
            else
                $query->orderBy($sort, $sort_type);
        }

        return $query;
    }
}
