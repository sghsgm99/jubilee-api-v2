<?php

namespace App\Models;

use App\Scopes\AccountScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BaseAccountModelTrait;
use Carbon\Carbon;
use App\Models\Services\SiteMenuService;
use App\Models\SitePage;

/**
 * Class SiteMenu
 *
 * Database Fields
 * @property int $id
 * @property int $site_id
 * @property int $account_id
 * @property boolean $is_top
 * @property boolean $is_bottom
 * @property string $title
 * @property string $slug
 * @property string $description
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 *
 * Relationships
 * @property-read Site $site
 * @property-read Article|Collection[] $articles
 *
 * Scopes
 * @method static Builder|SiteTag whereSiteId($value) // scopeWhereSiteId
 */
class SiteMenu extends Model
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
        'is_top' => 'boolean',
        'is_bottom' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new AccountScope());
    }

    public function Service(): SiteMenuService
    {
        return new SiteMenuService($this);
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


    public function scopeWhereSiteId(Builder $query, $id)
    {
        return $query->where('site_id', '=', $id);
    }

    public function pages()
    {
        return $this->belongsToMany(SitePage::class, 'site_menu_page', 'site_menu_id', 'site_page_id')->withTimestamps();
    }
}
