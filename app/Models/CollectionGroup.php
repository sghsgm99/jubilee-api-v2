<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BaseAccountModelTrait;
use App\Models\Services\CollectionGroupService;
use App\Models\CollectionCreative;

class CollectionGroup extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BaseAccountModelTrait;

    protected $table = 'collection_groups';

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
        'deleted_at' => 'datetime'
    ];

    public function Service(): CollectionGroupService
    {
        return new CollectionGroupService($this);
    }

    public function groupCreatives()
    {
        return $this->hasMany(CollectionGroupCreative::class, 'group_id');
    }

    public function collectionAds()
    {
        return $this->hasMany(CollectionAd::class, 'group_id');
    }

    public function scopeSearch(
        Builder $query,
        string $sort = null,
        string $sort_type = null
    )
    {
        if($sort) {
            $query->orderBy($sort, $sort_type);
        }

        return $query;
    }
}
