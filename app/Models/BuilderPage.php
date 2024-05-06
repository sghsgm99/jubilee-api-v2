<?php

namespace App\Models;

use App\Models\Services\BuilderPageService;
use App\Scopes\AccountScope;
use App\Traits\BaseAccountModelTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Class BuilderPage
 *
 * Database Fields
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property int $account_id
 * @property int $builder_site_id
 * @property string $title
 * @property string $slug
 * @property string $html
 * @property string $styling
 * @property string $seo
 * @property int $order
 *
 * Accessors
 * @property-read string $file_path // getFilePathAttribute
 * @property-read string $html_filename // getHtmlFilenameAttribute
 * @property-read string $html_file_path // getHtmlFilePathAttribute
 * @property-read string $css_filename // getCssFilenameAttribute
 * @property-read string $css_file_path // getCssFilePathAttribute
 *
 * Relationships
 * @property BuilderSite $site
 *
 * Scopes
 * @method static Builder|BuilderPage whereBuilderSiteId(int $builder_site_id) // scopeWhereBuilderSiteId
 * @method static Builder|BuilderPage search(string $search) // scopeSearch
 */
class BuilderPage extends Model
{
    use BaseAccountModelTrait;
    use SoftDeletes;

    protected $table = 'builder_pages';

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new AccountScope());
    }

    public function getFilePathAttribute(): string
    {
        return $this->site->getRootDestinationPath() . '/';
    }

    public function getHtmlFilenameAttribute(): string
    {
        return Str::slug(Str::lower($this->title), '_') . '.html';
    }

    public function getHtmlFilePathAttribute(): string
    {
        return $this->file_path . $this->html_filename;
    }

    public function getCssFilenameAttribute(): string
    {
        return Str::slug(Str::lower($this->title), '_') . '.css';
    }

    public function getCssFilePathAttribute(): string
    {
        return $this->file_path . $this->css_filename;
    }

    public function getPhysicalFiles(): array
    {
        $files = [];
        $fileSystem = Storage::disk('s3');

        if ($fileSystem->exists($this->html_file_path)) {
            $files[] = [
                'name' => $this->html_filename,
                'path' => $fileSystem->url($this->html_file_path),
            ];
        }

        if ($fileSystem->exists($this->css_file_path)) {
            $files[] = [
                'name' => $this->css_filename,
                'path' => $fileSystem->url($this->css_file_path),
            ];
        }

        return $files;
    }

    public function Service(): BuilderPageService
    {
        return new BuilderPageService($this);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(BuilderSite::class, 'builder_site_id');
    }

    public function scopeWhereBuilderSiteId(Builder $query, int $builder_site_id): Builder
    {
        return $query->where('builder_site_id', '=', $builder_site_id);
    }

    public function scopeSearch(Builder $query, string $search = null): Builder
    {
        if ($search) {
            $query->where('title', 'like', '%'.$search.'%');
        }
        return $query;
    }
}
