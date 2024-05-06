<?php

namespace App\Models;

use App\Interfaces\SeoableInterface;
use App\Traits\SeoableTrait;
use App\Interfaces\ImageableInterface;
use App\Models\Enums\ArticleStatusEnum;
use App\Models\Enums\ArticleTypeEnum;
use App\Models\Enums\StorageDiskEnum;
use App\Models\Services\Factories\FileServiceFactory;
use App\Scopes\AccountScope;
use App\Traits\BaseAccountModelTrait;
use App\Traits\ImageableTrait;
use Carbon\Carbon;
use Cviebrock\EloquentSluggable\Sluggable;
use Google\Service\CloudBuild\Build;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Models\Services\ArticleService;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class Article
 *
 * Database Fields
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property int $account_id
 * @property string $title
 * @property string $slug
 * @property string $content
 * @property ArticleStatusEnum $status
 * @property ArticleTypeEnum $type
 * @property int $user_id
 * @property int $toggle_length
 * @property bool $is_featured
 * @property bool $is_trending
 * @property int $revision
 * @property int|null $external_sync_id
 * @property string|null $external_sync_data
 * @property string|null $external_sync_image
 *
 * Relationships
 * @property Account $account
 * @property User|null $user
 * @property Site|Collection $sites
 * @property SiteCategory|Collection $categories
 * @property SiteTag|Collection $tags
 * @property SiteMenu|Collection $menus
 * @property Image $image
 * @property Image|Collection $images
 * @property ArticleHistory $articleHistory
 * @property ArticleQuizzes|Collection $quizzes
 * @property ArticleScroll|Collection $scrolls
 * @property ArticleGallery|Collection $galleries
 *
 * Scopes
 * @method static Builder|Article whereId($value) // scopeWhereId
 * @method static Builder|Article wherePublished() // scopeWherePublished
 */
class Article extends Model implements ImageableInterface, SeoableInterface
{
    use HasFactory;
    use BaseAccountModelTrait;
    use ImageableTrait;
    use SeoableTrait;
    use SoftDeletes;
    use Sluggable;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'external_sync_id',
    ];

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
        'external_sync_data' => 'array',
        'status' => ArticleStatusEnum::Class,
        'type' => ArticleTypeEnum::class,
        'is_featured' => 'boolean',
        'is_trending' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new AccountScope());
    }

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'title'
            ],
        ];
    }

    public function setSlugAttribute($value)
    {
        $this->attributes['slug'] = strtolower($value);
    }

    public function getRootDestinationPath(string $dir = null): string
    {
        $rootPath = "/articles/{$this->id}";

        if ($dir) {
            $rootPath .= '/' . trim($dir, '/');
        }

        return $rootPath;
    }

    public function getExternalSyncImageAttribute(): ?string
    {
        if (isset($this->attributes['external_sync_image']) && $this->attributes['external_sync_image']) {
            return $this->attributes['external_sync_image'];
        }

        if (! $this->external_sync_id && ! $this->external_sync_data) {
            return null;
        }

        return "https://explore.reference.com/content/{$this->external_sync_id}/{$this->external_sync_data['featured_path']}";
    }

    /**
     * @return string
     */
    public function getContentImagesDir()
    {
        return '/wysiwyg';
    }

    public function Service(): ArticleService
    {
        return new ArticleService($this);
    }

    public function FileServiceFactory(string $dir = null)
    {
        return FileServiceFactory::resolve($this, StorageDiskEnum::PUBLIC_DO(), $dir);
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
     * Relationship to the Site Model.
     */
    public function sites(
        string $sort = null,
        string $sort_type = null
    ) {
        $sites = $this->belongsToMany(Site::class)->withTimestamps();

        if ($sort) {
            $sites->orderBy($sort, $sort_type);
        }

        return $sites;
    }

    /**
     * Relationship to the ArticleQuizzes Model.
     */
    public function quizzes()
    {
        return $this->hasMany(ArticleQuizzes::class)->orderBy('order');
    }

    /**
     * Relationship to the ArticleScroll Model.
     */
    public function scrolls()
    {
        return $this->hasMany(ArticleScroll::class)->orderBy('order');
    }

    /**
     * Relationship to the ArticleGallery Model.
     */
    public function galleries()
    {
        return $this->hasMany(ArticleGallery::class)->orderBy('order');
    }

    /**
     * Relationship to the SiteCategory Model.
     */
    public function categories()
    {
        return $this->belongsToMany(SiteCategory::class)->withTimestamps();
    }

    /**
     * Relationship to the SiteTag Model.
     */
    public function tags()
    {
        return $this->belongsToMany(SiteTag::class)->withTimestamps();
    }

    /**
     * Relationship to the SiteMenu Model.
     */
    public function menus()
    {
        return $this->belongsToMany(SiteMenu::class)->withTimestamps();
    }

    /**
     * Relationship to the ArticleHistory Model.
     */
    public function articleHistory()
    {
        return $this->hasMany(ArticleHistory::class, 'article_id', 'id');
    }

    /**
     * @param Builder $query
     * @param string|null $search
     * @param ArticleStatusEnum|null $platform
     * @return Builder
     */
    public function scopeSearch(
        Builder $query,
        string $search = null,
        ArticleStatusEnum $status = null,
        string $sort = null,
        string $sort_type,
        ArticleTypeEnum $type = null,
        int $owner = null
    ) {
        if ($search) {
            $query->where('title', 'like', '%' . $search . '%');
        }

        if ($status->isNotUndefined()) {
            $query->where('status', '=', $status);
        }

        if ($sort) {
            $query->orderBy($sort, $sort_type);
        }

        if ($type->isNotUndefined()){
            $query->where('type', '=', $type->value);
        }

        if ($owner){
            $query->where('user_id', '=', $owner);
        }

        return $query;
    }

    public function scopeWhereId(Builder $query, int $id)
    {
        return $query->where('id', '=', $id);
    }

    public function scopeWherePublished(Builder $query)
    {
        return $query->where('status', '=', ArticleStatusEnum::PUBLISHED);
    }
}
