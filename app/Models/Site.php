<?php

namespace App\Models;

use App\Interfaces\ImageableInterface;
use App\Models\Enums\StorageDiskEnum;
use App\Models\Services\Factories\FileServiceFactory;
use App\Models\Services\Factories\SiteServiceFactory;
use App\Scopes\AccountScope;
use App\Services\AnalyticsService;
use App\Traits\BaseAccountModelTrait;
use App\Traits\ImageableTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Models\Enums\SiteStatusEnum;
use App\Models\Enums\SitePlatformEnum;
use App\Models\Services\SiteService;

/**
 * Class Site
 *
 * Database Fields
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property int $user_id
 * @property int $account_id
 * @property string $name
 * @property string $url
 * @property string $api_key
 * @property string $api_jubilee_key
 * @property string $client_key // wordpress client key
 * @property string $client_secret_key // wordpress client secret key
 * @property string $api_callback
 * @property array $api_permissions // wordpress consumer, token, and signature
 * @property SitePlatformEnum $platform
 * @property SiteStatusEnum $status
 * @property string $description
 * @property string $host
 * @property string $ssh_username
 * @property string $ssh_password
 * @property string $path
 * @property string $view_id
 * @property string $analytic_file
 * @property string $analytic_script
 *
 * Accessors
 * @property-read bool $is_wordpress_integrated // getIsWordpressIntegratedAttribute
 *
 * Relationships
 * @property User $user
 * @property SiteCategory|Collection $categories
 * @property SiteTag|Collection $tags
 * @property SiteMenu|Collection $menus
 * @property SiteSetting|Collection $settings
 *
 * Scopes
 * @method static Builder|Site whereId($value) // scopeWhereId
 * @method static Builder|Site whereUrl($value) // scopeWhereUrl
 */
class Site extends Model implements ImageableInterface
{
    use HasFactory;
    use SoftDeletes;
    use BaseAccountModelTrait;
    use ImageableTrait;

    protected $appends = ['is_wordpress_integrated'];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'api_permissions' => 'array',
        'platform' => SitePlatformEnum::class,
        'status' => SiteStatusEnum::class
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new AccountScope());
    }

    public function getRootDestinationPath(string $dir = null): string
    {
        $rootPath = "/sites/{$this->id}";

        if ($dir) {
            $rootPath .= '/' . trim($dir, '/');
        }

        return $rootPath;
    }

    public function getAnalyticFileAttribute()
    {
        if (! $this->attributes['analytic_file']) {
            return null;
        }

        return storage_path("app{$this->attributes['analytic_file']}");
    }

    public function getIsWordpressIntegratedAttribute(): bool
    {
        return $this->platform->is(SitePlatformEnum::WORDPRESS())
            && isset($this->api_permissions['is_verified'])
            && $this->api_permissions['is_verified'];
    }

    public function Service(): SiteService
    {
        return new SiteService($this);
    }

    public function FileServiceFactory(string $dir = null, StorageDiskEnum $storageDiskEnum = null)
    {
        if ($storageDiskEnum === null) {
            $storageDiskEnum = StorageDiskEnum::PUBLIC_DO();
        }

        return FileServiceFactory::resolve($this, $storageDiskEnum, $dir);
    }

    public function getContentImagesDir()
    {
        return '/wysiwyg';
    }

    public function AnalyticService(): AnalyticsService
    {
        return AnalyticsService::resolve($this);
    }

    /**
     * @return \App\Services\Wordpress\WordpressService
     */
    public function SiteServiceFactory()
    {
        return SiteServiceFactory::resolve($this);
    }

    /**
     * Relationship to the User Model.
     *
     * @return BelongsTo|User
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relationship to the SiteMenu Modal
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function menus()
    {
        return $this->hasMany(SiteMenu::class);
    }

    /**
     * Relationship to the SiteSettings Modal
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function settings()
    {
        return $this->hasOne(SiteSetting::class);
    }

    /**
     * Relationship to the SiteCategory Modal
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function categories()
    {
        return $this->hasMany(SiteCategory::class);
    }

    /**
     * Relationship to the SiteTag Modal
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tags()
    {
        return $this->hasMany(SiteTag::class);
    }

    /**
     * The articles that belong to the site.
     */
    public function articles(string $search = null)
    {
        $result = $this->belongsToMany(Article::class);

        if ($search) {
            $result->where('title', 'like', '%' . $search . '%')
                ->where('is_featured', false)
                ->where('is_trending', false);
        }

        return $result->withTimestamps();
    }

    public function scopeWhereId(Builder $query, int $id)
    {
        return $query->where('id', '=', $id);
    }

    /**
     * @param Builder $query
     * @param string|null $search
     * @param SitePlatformEnum|null $platform
     * @param SiteStatusEnum|null $status
     * @return Builder
     */
    public function scopeSearch(
        Builder $query,
        string $search = null,
        SitePlatformEnum $platform = null,
        SiteStatusEnum $status = null,
        int $owner = null,
        string $sort = null,
        string $sort_type = null
    )
    {
        if ($search) {
            $query->where(function ($q) use($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('url', 'like', '%'.$search.'%');
            });
        }

        if ($platform->isNotUndefined()) {
            $query->where('platform', '=', $platform);
        }

        if ($status->isNotUndefined()) {
            $query->where('status', '=', $status);
        }

        if($owner) {
            $query->where('user_id', '=', $owner);
        }

        if($sort) {
            $query->orderBy($sort, $sort_type);
        }

        return $query;
    }

    public function scopeWhereUrl(Builder $builder, string $hostname)
    {
        return $builder->where('url', 'LIKE', "%{$hostname}%");
    }

    public function ads()
    {
        return $this->hasMany(SiteAd::class);
    }

    public function redirects()
    {
        return $this->hasMany(SiteRedirect::class);
    }
}
