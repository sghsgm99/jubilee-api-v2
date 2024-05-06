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
use App\Models\Services\SiteSettingService;

/**
 * Class SiteSetting
 *
 * Database Fields
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property int $site_id
 * @property int $account_id
 * @property int $theme_id
 * @property string $title
 * @property string $description
 * @property string $about_us_blurb
 * @property string $contact_us_blurb
 * @property string $header_tags
 * @property string $body_tags
 * @property string $footer_tags
 * @property bool $is_index
 * @property int $status
 *
 * Relationships
 * @property Site|Collection $site
 * @property SiteTheme|Collection $siteTheme
 */
class SiteSetting extends Model
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
        'deleted_at' => 'datetime',
        'is_index' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new AccountScope());
    }

    public function Service(): SiteSettingService
    {
        return new SiteSettingService($this);
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
     * Relationship to the SiteTheme Modal
     *
     * @return BelongsTo
     */
    public function siteTheme()
    {
        return $this->belongsTo(SiteTheme::class, 'theme_id');
    }

    public function scopeWhereSiteId(Builder $query, $id)
    {
        return $query->where('site_id', '=', $id);
    }
}
