<?php

namespace App\Models;

use App\Scopes\AccountScope;
use App\Traits\BaseAccountModelTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Services\SiteTagService;

/**
 * Class SiteTag
 *
 * Database Fields
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property int $account_id
 * @property int $site_id
 * @property int $tag_id
 * @property string $label
 *
 * Relationships
 * @property-read Site $site
 *
 * Scopes
 * @method static Builder|SiteTag whereTagId($value) // scopeWhereTagId
 * @method static Builder|SiteTag whereSiteId($value) // scopeWhereSiteId
 */
class SiteTag extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BaseAccountModelTrait;

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

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new AccountScope());
    }

    public function Service(): SiteTagService
    {
        return new SiteTagService($this);
    }

    /**
     * Relationship to the Site Model.
     *
     * @return BelongsTo
     */
    public function site()
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    /**
     * Relationship to the Article Model.
     */
    public function articles()
    {
        return $this->belongsToMany(Article::class)->withTimestamps();
    }


    public function scopeWhereTagId(Builder $query, $id)
    {
        return $query->where('tag_id', '=', $id);
    }

    public function scopeWhereSiteId(Builder $query, $id)
    {
        return $query->where('site_id', '=', $id);
    }
}
