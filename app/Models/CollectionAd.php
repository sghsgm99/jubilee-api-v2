<?php

namespace App\Models;

use App\Scopes\AccountScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BaseAccountModelTrait;
use App\Models\Services\CollectionAdService;
use App\Models\Enums\CollectionAdStatusEnum;

class CollectionAd extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BaseAccountModelTrait;

    protected $table = 'collection_ads';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [];

    protected $appends = [];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'add_images' => 'array',
        'add_title' => 'array',
        'add_text' => 'array',
        'add_headline' => 'array',
        'add_url' => 'array',
        'add_call_to_action' => 'array',
        'status' => CollectionAdStatusEnum::class,
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new AccountScope());
    }

    public function Service(): CollectionAdService
    {
        return new CollectionAdService($this);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function channel()
    {
        return $this->belongsTo(Channel::class, 'channel_id');
    }

    public function campaign()
    {
        return $this->belongsTo(FacebookCampaign::class, 'campaign_id');
    }

    public function adSet()
    {
        return $this->belongsTo(FacebookAdset::class, 'adset_id');
    }

    public function group()
    {
        return $this->belongsTo(CollectionGroup::class, 'group_id');
    }

    public function facebookAds()
    {
        return $this->belongsToMany(FacebookAd::class, 'collection_facebook_ads', 'collection_ad_id', 'facebook_ad_id')->withTimestamps();
    }

    public function scopeSearch(
        Builder $query,
        int $cid,
        string $search = null,
        string $sort = null,
        string $sort_type = null
    )
    {
        $query->where('collection_id', $cid);

        if ($search) {
            $query->whereHas('group', function($q) use($search){
                $q->where(function ($get) use($search) {
                    $get->where('name', 'like', '%'.$search.'%');
                });
            });
        }

        if($sort) {
            $query->orderBy($sort, $sort_type);
        }

        return $query;
    }
}
